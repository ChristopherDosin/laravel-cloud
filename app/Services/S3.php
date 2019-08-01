<?php

namespace App\Services;

use Exception;
use Aws\S3\S3Client;
use App\DatabaseBackup;
use App\StorageProvider;
use App\Contracts\StorageProviderClient;

class S3 implements StorageProviderClient
{
    /**
     * The storage provider instance.
     *
     * @var \App\StorageProvider
     */
    public $provider;

    /**
     * Create a new storage provider instance.
     *
     * @param  \App\StorageProvider  $provider
     * @return void
     */
    public function __construct(StorageProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Determine if the provider credentials are valid.
     *
     * @return bool
     */
    public function valid()
    {
        try {
            if (! isset($this->provider->meta['bucket'])) {
                return false;
            }

            return $this->hasBucket($this->provider->meta['bucket']);
        } catch (Exception $e) {
            report($e);

            return false;
        }
    }

    /**
     * Determine if the given bucket exists.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasBucket($name)
    {
        try {
            $this->client()->getBucketLocation([
                'Bucket' => $name,
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Create a bucket with the given name.
     *
     * @param  string  $name
     * @return void
     */
    public function createBucket($name)
    {
        $this->client()->createBucket([
            'Bucket' => $name,
        ]);
    }

    /**
     * Delete the given bucket.
     *
     * @param  string  $name
     * @return void
     */
    public function deleteBucket($name)
    {
        $this->client()->deleteBucket([
            'Bucket' => $name,
        ]);
    }

    /**
     * Determine if the given object exists.
     *
     * @param  string  $path
     * @return bool
     */
    public function has($path)
    {
        try {
            $response = $this->client()->headObject([
                'Bucket' => $this->provider->bucket(),
                'Key' => $path,
            ]);

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Store an object at the given path.
     *
     * @param  string  $path
     * @param  string  $data
     * @return void
     */
    public function put($path, $data)
    {
        $this->client()->putObject([
            'Bucket' => $this->provider->bucket(),
            'Key' => $path,
            'Body' => $data,
        ]);
    }

    /**
     * Get the size of the object in megabytes.
     *
     * @param  string  $path
     * @return int
     */
    public function size($path)
    {
        try {
            $response = $this->client()->headObject([
                'Bucket' => $this->provider->bucket(),
                'Key' => $path,
            ]);

            return (int) round($response['ContentLength'] / 1024 / 1024);
        } catch (Exception $e) {
            report($e);

            return;
        }
    }

    /**
     * Delete the object at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function delete($path)
    {
        $this->client()->deleteObject([
            'Bucket' => $this->provider->bucket(),
            'Key' => $path,
        ]);
    }

    /**
     * Get the configuration script for the storage provider.
     *
     * @return string
     */
    public function configurationScript()
    {
        return view('scripts.storage-provider-configuration.s3', [
            'provider' => $this->provider,
        ])->render();
    }

    /**
     * Get the upload script for the storage provider.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return string
     */
    public function uploadScript(DatabaseBackup $backup)
    {
        return sprintf(
            "aws s3 cp /home/cloud/backups/%s s3://%s/%s",
            basename($backup->backup_path),
            $backup->storageProvider->bucket(),
            $backup->backup_path
        );
    }

    /**
     * Get the download script for the storage provider.
     *
     * @param  \App\DatabaseBackup  $backup
     * @return string
     */
    public function downloadScript(DatabaseBackup $backup)
    {
        return sprintf(
            "aws s3 cp s3://%s/%s /home/cloud/restores/%s",
            $backup->storageProvider->bucket(),
            $backup->backup_path,
            basename($backup->backup_path)
        );
    }

    /**
     * Get an S3 client instance for the storage provider.
     *
     * @return \AWS\S3\S3Client
     */
    protected function client()
    {
        return new S3Client([
            'version' => 'latest',
            'credentials' => [
                'key' => $this->provider->meta['key'],
                'secret' => $this->provider->meta['secret'],
            ],
            'region' => $this->provider->meta['region'],
        ]);
    }
}
