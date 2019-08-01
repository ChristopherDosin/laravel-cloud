<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\S3;
use App\StorageProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class S3Test extends TestCase
{
    use RefreshDatabase;


    public function setUp()
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }


    public function test_can_determine_if_credentials_are_valid()
    {
        $provider = factory(StorageProvider::class)->create();
        $s3 = new S3($provider);

        $this->assertTrue($s3->valid());


        $provider = factory(StorageProvider::class)->create([
            'meta' => ['key' => 'foo', 'secret' => 'baz', 'region' => 'us-east-1', 'bucket' => 'foobarbaz']
        ]);
        $s3 = new S3($provider);

        $this->assertFalse($s3->valid());
    }


    public function test_can_create_and_delete_buckets()
    {
        $provider = factory(StorageProvider::class)->create();

        $s3 = new S3($provider);
        $s3->createBucket('laravel-cloud-dummy');

        sleep(1);

        retry(10, function () use ($s3) {
            $this->assertTrue($s3->hasBucket('laravel-cloud-dummy'));
        }, 1000);

        $s3->deleteBucket('laravel-cloud-dummy');

        sleep(1);

        retry(10, function () use ($s3) {
            $this->assertFalse($s3->hasBucket('laravel-cloud-dummy'));
        }, 1000);
    }


    public function test_can_delete_files()
    {
        $provider = factory(StorageProvider::class)->create();
        $s3 = new S3($provider);

        $s3->put('hello-world', 'Hello World');
        $this->assertEquals(0, $s3->size('hello-world'));
        $this->assertTrue($s3->has('hello-world'));

        $s3->delete('hello-world');
        $this->assertFalse($s3->has('hello-world'));
    }
}
