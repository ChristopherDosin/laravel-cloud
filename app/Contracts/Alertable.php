<?php

namespace App\Contracts;

interface Alertable
{
    /**
     * Create an alert for the given instance.
     *
     * @return \App\Alert
     */
    public function toAlert();
}
