<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
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
        'private_key',
        'certificate',
    ];

    /**
     * Get the project that owns the certificate.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Determine if this certificate is active.
     *
     * @return bool
     */
    public function active()
    {
        return $this->project->activeCertificates()->contains(function ($certificate) {
            return $certificate->id === $this->id;
        });
    }
}
