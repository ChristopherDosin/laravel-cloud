<?php

namespace App;

use Carbon\Carbon;
use App\Jobs\SyncBalancer;
use App\Callbacks\Dispatch;
use App\Jobs\ProvisionBalancer;
use App\Jobs\UpdateStackDnsRecords;
use App\Jobs\DeleteServerOnProvider;
use App\Callbacks\MarkAsProvisioned;
use Illuminate\Database\Eloquent\Model;
use App\Scripts\SyncBalancer as SyncBalancerScript;
use App\Contracts\Provisionable as ProvisionableContract;

class Balancer extends Model implements ProvisionableContract
{
    use Provisionable;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'self_signs' => 'boolean',
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
        'private_key', 'sudo_password',
    ];

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
     * Sync the balancer's configuration with the current stacks.
     *
     * @param  int  $delay
     * @return void
     */
    public function sync($delay = 0)
    {
        Jobs\SyncBalancer::dispatch($this)->delay($delay);
    }

    /**
     * Sync the balancer's configuration with the current stacks.
     *
     * @return \App\Task
     */
    public function syncNow()
    {
        return $this->run(new SyncBalancerScript($this));
    }

    /**
     * Determine if the balancer should self-sign TLS certificates.
     *
     * @return bool
     */
    public function selfSignsCertificates()
    {
        return $this->tls === 'self-signed';
    }

    /**
     * Dispatch the job to provision the balancer.
     *
     * @return void
     */
    public function provision()
    {
        ProvisionBalancer::dispatch($this);

        $this->update(['provisioning_job_dispatched_at' => Carbon::now()]);
    }

    /**
     * Run the provisioning script on the balancer.
     *
     * @return \App\Task|null
     */
    public function runProvisioningScript()
    {
        if ($this->isProvisioning()) {
            return;
        }

        $this->markAsProvisioning();

        return $this->runInBackground(new Scripts\ProvisionBalancer($this), [
            'then' => [
                MarkAsProvisioned::class,
                new Dispatch(SyncBalancer::class)
            ],
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
        if ($this->address) {
            UpdateStackDnsRecords::dispatch(
                $this->project, $this->address->public_address
            );
        }

        DeleteServerOnProvider::dispatch(
            $this->project, $this->providerServerId()
        );

        $this->address()->delete();
        $this->tasks()->delete();

        parent::delete();
    }
}
