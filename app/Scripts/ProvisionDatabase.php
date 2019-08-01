<?php

namespace App\Scripts;

use App\Database;

class ProvisionDatabase extends ProvisioningScript
{
    /**
     * The displayable name of the script.
     *
     * @var string
     */
    public $name = 'Provisioning Database';

    /**
     * The database instance.
     *
     * @var \App\Database
     */
    public $database;

    /**
     * Create a new script instance.
     *
     * @param  \App\Database  $database
     * @return void
     */
    public function __construct(Database $database)
    {
        parent::__construct($database);

        $this->database = $database;
    }

    /**
     * Get the contents of the script.
     *
     * @return string
     */
    public function script()
    {
        return view('scripts.database.provision', [
            'script' => $this,
            'database' => $this->database,
            'databasePassword' => $this->database->password,
        ])->render();
    }
}
