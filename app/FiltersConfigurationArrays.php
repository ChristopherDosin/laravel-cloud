<?php

namespace App;

use Illuminate\Support\Arr;

trait FiltersConfigurationArrays
{
    /**
     * The valid daemon attributes.
     *
     * @var array
     */
    protected $daemonAttributes = [
        'command',
        'directory',
        'processes',
        'wait',
    ];

    /**
     * The valid schedule attributes.
     *
     * @var array
     */
    protected $scheduleAttributes = [
        'command',
        'frequency',
        'user',
    ];

    /**
     * Filter the given daemon definition.
     *
     * @param  array  $daemons
     * @return array
     */
    protected function filterDaemons(array $daemons)
    {
        return collect($daemons)->mapWithKeys(function ($daemon, $name) {
            return [$name => Arr::only($daemon, $this->daemonAttributes)];
        })->all();
    }

    /**
     * Filter the given schedule definition.
     *
     * @param  array  $schedule
     * @return array
     */
    protected function filterSchedule(array $schedule)
    {
        return collect($schedule)->mapWithKeys(function ($schedule, $name) {
            return [$name => Arr::only($schedule, $this->scheduleAttributes)];
        })->all();
    }
}
