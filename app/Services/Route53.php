<?php

namespace App\Services;

use Exception;
use App\Stack;
use App\Contracts\DnsProvider;
use Aws\Route53\Route53Client;

class Route53 implements DnsProvider
{
    /**
     * The underlying Route 53 client.
     *
     * @var \Aws\Route53\Route53Client
     */
    public $client;

    /**
     * Create a new Route 53 service instance.
     *
     * @param  \Aws\Route53\Route53Client  $client
     * @return void
     */
    public function __construct(Route53Client $client)
    {
        $this->client = $client;
    }

    /**
     * Add a DNS record for the given stack.
     *
     * @param  \App\Stack  $stack
     * @return string
     */
    public function addRecord(Stack $stack)
    {
        $this->deleteRecord($stack);

        return tap($this->updateRecord('CREATE', $stack)['ChangeInfo']['Id'], function ($id) use ($stack) {
            $stack->update([
                'dns_record_id' => $id,
                'dns_address' => $stack->entrypoint(),
            ]);
        });
    }

    /**
     * Determine if the stack's DNS record has propagated.
     *
     * @param  \App\Stack  $stack
     * @return bool
     */
    public function propagated(Stack $stack)
    {
        return $stack->dns_record_id && $this->client->getChange([
            'Id' => $stack->dns_record_id,
        ])['ChangeInfo']['Status'] == 'INSYNC';
    }

    /**
     * Delete a DNS record for the given stack.
     *
     * @param  \App\Stack  $stack
     * @return void
     */
    public function deleteRecord(Stack $stack)
    {
        if (! $stack->dns_address) {
            return;
        }

        try {
            $this->updateRecord('DELETE', $stack);
        } catch (Exception $e) {
            report($e);
        }

        $stack->update([
            'dns_record_id' => null,
            'dns_address' => null,
        ]);
    }

    /**
     * Delete a DNS record for the given name and address.
     *
     * @param  string  $name
     * @param  string  $ipAddress
     * @return void
     */
    public function deleteRecordByName($name, $ipAddress)
    {
        return $this->updateRecordByName('DELETE', $name, $ipAddress);
    }

    /**
     * Perform an action on the Route 53 record.
     *
     * @param  string  $action
     * @param  \App\Stack  $stack
     * @return mixed
     */
    protected function updateRecord($action, Stack $stack)
    {
        return $this->updateRecordByName(
            $action, $stack->url,
            $action == 'CREATE' ? $stack->entrypoint() : $stack->dns_address
        );
    }

    /**
     * Perform an action on the Route 53 record.
     *
     * @param  string  $action
     * @param  string  $name
     * @param  string  $ipAddress
     * @return mixed
     */
    protected function updateRecordByName($action, $name, $ipAddress)
    {
        return $this->client->changeResourceRecordSets([
            'HostedZoneId' => 'ZDV4H7FXFYGN0',
            'ChangeBatch' => [
                'Changes' => [
                    [
                        'Action' => $action,
                        'ResourceRecordSet' => [
                            'Name' => $name.'.laravel.build',
                            'Type' => 'A',
                            'ResourceRecords' => [
                                ['Value' => $ipAddress],
                            ],
                            'TTL' => 60,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
