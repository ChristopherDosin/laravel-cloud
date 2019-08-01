<?php

namespace App\Http\Controllers;

use App\Jobs\PruneTasks;

class ScheduleController extends Controller
{
    /**
     * Prune the old tasks from the system.
     *
     * @return Response
     */
    public function pruneTasks()
    {
        PruneTasks::dispatch();
    }
}
