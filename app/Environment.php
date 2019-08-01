<?php

namespace App;

use App\Jobs\PromoteStack;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;

class Environment extends Model
{
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
        'encryption_key',
        'variables',
    ];

    /**
     * Get the project that the environment belongs to.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Get the creator of the environment.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get the promoted stack for the environment.
     *
     * @return Stack
     */
    public function promotedStack()
    {
        return $this->stacks->first->promoted;
    }

    /**
     * Get all of the stacks for the environment.
     */
    public function stacks()
    {
        return $this->hasMany(Stack::class);
    }

    /**
     * Promote the given stack so that it serves production URLs.
     *
     * @param  \App\Stack  $stack
     * @param  array  $options
     * @return bool
     */
    public function promote(Stack $stack, array $options = [])
    {
        if (! $stack->promotable()) {
            return false;
        }

        PromoteStack::dispatch($stack, $options);

        return true;
    }

    /**
     * Mark the given stack as promoted.
     *
     * @param  \App\Stack  $stack
     * @return void
     */
    public function markAsPromoted(Stack $stack)
    {
        $this->stacks()->update(['promoted' => false]);

        $this->stacks()->where('id', $stack->id)->update(['promoted' => true]);
    }

    /**
     * Get the lock needed to promote stacks.
     *
     * @return \Illuminate\Contracts\Cache\Lock
     */
    public function promotionLock()
    {
        return Cache::store('redis')->lock('promote:'.$this->id, 180);
    }

    /**
     * Determine if this is the production environment.
     *
     * @return bool
     */
    public function isProduction()
    {
        return $this->name == 'production';
    }

    /**
     * Get the provider gateway for the environment.
     *
     * @return mixed
     */
    public function withProvider()
    {
        return $this->project->withProvider();
    }

    /**
     * Delete the environment.
     *
     * @return void
     */
    public function delete()
    {
        $this->stacks->each->delete();

        return parent::delete();
    }
}
