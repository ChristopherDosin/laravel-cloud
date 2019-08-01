<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Hook extends Model
{
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
        'published' => 'boolean',
    ];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * Get the project for the hook.
     *
     * @return \App\Project
     */
    public function project()
    {
        return $this->stack->project();
    }

    /**
     * Get the source control provider for the hook.
     *
     * @return \App\SourceProvider
     */
    public function sourceProvider()
    {
        return $this->stack->project()->sourceProvider;
    }

    /**
     * Get the repository for the hook.
     *
     * @return string
     */
    public function repository()
    {
        return $this->project()->repository;
    }

    /**
     * Get the stack the hook belongs to.
     */
    public function stack()
    {
        return $this->belongsTo(Stack::class, 'stack_id');
    }

    /**
     * Publish the hook to the source control provider.
     *
     * @return void
     */
    public function publish()
    {
        $this->sourceProvider()->client()->publishHook($this);
    }

    /**
     * Remove the hook from the source control provider.
     *
     * @return void
     */
    public function unpublish()
    {
        if ($this->published) {
            $this->sourceProvider()->client()->unpublishHook($this);
        }
    }

    /**
     * Determine if the given hook payload is a test.
     *
     * @param  \App\Hook  $hook
     * @param  array  $payload
     * @return bool
     */
    public function isTest(array $payload)
    {
        return $this->sourceProvider()->client()->isTestHookPayload(
            $this, $payload
        );
    }

    /**
     * Determine if this hook responds to the given source provider event payload.
     *
     * @param  arary  $payload
     * @return bool
     */
    public function receives(array $payload)
    {
        return ! $this->published || $this->sourceProvider()->client()->receivesHookPayload(
            $this, $payload
        );
    }

    /**
     * Get the URL to be used for hook deployments.
     *
     * @return string
     */
    public function url()
    {
        return url(config('app.url')."/api/hook-deployment/{$this->id}/{$this->token}");
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
        $this->unpublish();

        return parent::delete();
    }

    /**
     * Convert the model instance to an array.
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(parent::toArray(), [
            'url' => $this->url(),
        ]);
    }
}
