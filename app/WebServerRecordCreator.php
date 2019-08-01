<?php

namespace App;

class WebServerRecordCreator extends ServerRecordCreator
{
    /**
     * The server type.
     *
     * @var string
     */
    protected $type = 'web';

    /**
     * Get the relationship for the server type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function relation()
    {
        return $this->stack->webServers();
    }
}
