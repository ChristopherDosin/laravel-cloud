<?php

namespace App\Contracts;

use App\DatabaseBackup;

interface StorageProviderClient
{
    /**
     * Determine if the storage provider credentials are valid.
     *
     * @return bool
     */
    public function valid();

    /**
     * Determine if the given bucket exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasBucket($name);

    /**
     * Create a bucket with the given name.
     *
     * @param  string  $name
     * @return void
     */
    public function createBucket($name);

    /**
     * Delete the given bucket.
     *
     * @param  string  $name
     * @return void
     */
    public function deleteBucket($name);

    /**
     * Determine if the given object exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function has($path);

    /**
     * Store an object at the given path.
     *
     * @param  string  $path
     * @param  string  $data
     * @return void
     */
    public function put($path, $data);

    /**
     * Delete the object at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function delete($path);

    /**
     * Get the configuration script for the storage provider.
     *
     * @return string
     */
    public function configurationScript();

    /**
     * Get the upload script for the storage provider.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return string
     */
    public function uploadScript(DatabaseBackup $backup);

    /**
     * Get the download script for the storage provider.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return string
     */
    public function downloadScript(DatabaseBackup $backup);
}
