<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Contracts\DnsProvider;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class DeleteDnsRecord implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The name of the stack.
     *
     * @var string
     */
    public $name;

    /**
     * The IP address of the record.
     *
     * @var string
     */
    public $ipAddress;

    /**
     * Create a new job instance.
     *
     * @param  string  $name
     * @param  string  $ipAddress
     * @return void
     */
    public function __construct($name, $ipAddress)
    {
        $this->name = $name;
        $this->ipAddress = $ipAddress;
    }

    /**
     * Execute the job.
     *
     * @param  \App\Contracts\DnsProvider  $dns
     * @return void
     */
    public function handle(DnsProvider $dns)
    {
        $dns->deleteRecordByName($this->name, $this->ipAddress);
    }
}
