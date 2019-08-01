<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait Prunable
{
    /**
     * Prune tasks older than the given date.
     *
     * @param  \Carbon\Carbon  $date
     * @param  int  $limit
     * @return int
     */
    public static function prune(Carbon $date, $limit = 500)
    {
        $instance = new static;

        $total = 0;

        do {
            $affected = DB::delete(
                'delete from '.$instance->getTable().' where created_at <= ? order by id limit '.$limit,
                [$date->format('Y-m-d H:i:s')]
            );

            $total += $affected;
        } while ($affected > 0);

        return $total;
    }
}
