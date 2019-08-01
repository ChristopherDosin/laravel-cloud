<?php

namespace App;

class AppServerRecordCreator extends ServerRecordCreator
{
    /**
     * The server type.
     *
     * @var string
     */
    protected $type = 'app';

    /**
     * Get the relationship for the server type.
     *
     * @return \Illuminate\Database\Eloquent\Relations\Relation
     */
    public function relation()
    {
        return $this->stack->appServers();
    }

    /**
     * Get the custom attributes for the servers.
     *
     * @return array
     */
    protected function attributes()
    {
        return [
            'database_username' => 'cloud',
            'database_password' => str_random(40),
        ];
    }
}
