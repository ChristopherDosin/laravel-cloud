<?php

namespace App;

class WorkerServerRecordCreator extends ServerRecordCreator
{
    /**
     * The server type.
     *
     * @var string
     */
    protected $type = 'worker';

    /**
     * Get the relationship for the server type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function relation()
    {
        return $this->stack->workerServers();
    }
}
