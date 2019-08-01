<?php

namespace App;

use Carbon\Carbon;
use App\Jobs\SyncNetwork;
use App\Jobs\ProvisionDatabase;
use App\Jobs\StoreDatabaseBackup;
use App\Jobs\DeleteServerOnProvider;
use App\Callbacks\MarkAsProvisioned;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use App\Contracts\Provisionable as ProvisionableContract;

class Database extends Model implements ProvisionableContract
{
    use Provisionable;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'allows_access_from' => 'json',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'private_key', 'sudo_password', 'password',
    ];

    /**
     * Get the project that the database belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * The database backups associated with the database.
     */
    public function backups()
    {
        return $this->hasMany(DatabaseBackup::class)->latest('id');
    }

    /**
     * The database restores associated with the database.
     */
    public function restores()
    {
        return $this->hasMany(DatabaseRestore::class)->latest('id');
    }

    /**
     * Get all of the stacks attached to the database.
     */
    public function stacks()
    {
        return $this->belongsToMany(
            Stack::class, 'stack_databases', 'database_id', 'stack_id'
        );
    }

    /**
     * Determine if the given user can SSH into the balancer.
     *
     * @param  \App\User  $user
     * @return bool
     */
    public function canSsh(User $user)
    {
        return $user->canAccessProject($this->project);
    }

    /**
     * Sync the network for the database.
     *
     * @param  int  $delay
     * @return void
     */
    public function syncNetwork($delay = 0)
    {
        SyncNetwork::dispatch($this)->delay($delay);
    }

    /**
     * Determine if the database's network is synced.
     *
     * @return bool
     */
    public function networkIsSynced()
    {
        return count(array_diff(
            $this->shouldAllowAccessFrom(),
            $this->allows_access_from
        )) === 0;
    }

    /**
     * Determine if the database allows access from a given IP address.
     *
     * @param  object|string  $address
     * @return bool
     */
    public function allowsAccessFrom($address)
    {
        return in_array(
            is_object($address) ? $address->address->public_address : $address,
            $this->allows_access_from
        );
    }

    /**
     * Get all of the IP addresses the database should allow access from.
     *
     * @return array
     */
    public function shouldAllowAccessFrom()
    {
        return $this->stacks->flatMap(function ($stack) {
            return $stack->allIpAddresses();
        })->all();
    }

    /**
     * Get a network lock instance for the provisionable.
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function networkLock()
    {
        return Cache::store('redis')->lock('ufw:'.$this->id, 30);
    }

    /**
     * Create a new backup of the database.
     *
     * @param  \App\StorageProvider  $provider
     * @param  string  $databaseName
     * @return \App\DatabaseBackup
     */
    public function backup(StorageProvider $provider, $databaseName)
    {
        return tap($this->backups()->create([
            'storage_provider_id' => $provider->id,
            'database_name' => $databaseName,
            'backup_path' => DatabaseBackup::newPathFor($this->project),
            'status' => 'pending',
            'output' => '',
        ]), function ($backup) {
            $this->trimBackups($backup);

            StoreDatabaseBackup::dispatch($backup);
        });
    }

    /**
     * Trim the backups for a given database.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return void
     */
    protected function trimBackups(DatabaseBackup $backup)
    {
        $backups = $this->backups()->where(
            'database_name', $backup->database_name
        )->get();

        if (count($backups) > 20) {
            $backups->slice(20 - count($backups))->each->delete();
        }
    }

    /**
     * Get the environment variable name for the database.
     *
     * @return string
     */
    public function variableName()
    {
        return strtoupper(str_replace('-', '_', $this->name));
    }

    /**
     * Dispatch the job to provision the server.
     *
     * @return void
     */
    public function provision()
    {
        ProvisionDatabase::dispatch($this);

        $this->update(['provisioning_job_dispatched_at' => Carbon::now()]);
    }

    /**
     * Run the provisioning script on the server.
     *
     * @return \App\Task|null
     */
    public function runProvisioningScript()
    {
        if ($this->isProvisioning()) {
            return;
        }

        $this->markAsProvisioning();

        return $this->runInBackground(new Scripts\ProvisionDatabase($this), [
            'then' => [MarkAsProvisioned::class],
        ]);
    }

    /**
     * Delete the model from the database.
     *
     * @return bool|null
     *
     * @throws \Exception
     */
    public function delete()
    {
        DeleteServerOnProvider::dispatch(
            $this->project, $this->providerServerId()
        );

        $this->stacks()->detach();
        $this->address()->delete();
        $this->tasks()->delete();

        parent::delete();
    }
}
