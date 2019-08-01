<?php

namespace App\Http\Controllers\API;

use App\Task;
use Exception;
use App\Jobs\FinishTask;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class CallbackController extends Controller
{
    /**
     * Handle the callback for a task.
     *
     * @param  Request  $request
     * @param  string  $hashid
     * @return Response
     */
    public function handle(Request $request, $hashid)
    {
        try {
            $task = Task::findOrFail(hashid_decode($hashid));
        } catch (Exception $e) {
            abort(404);
        }

        abort_unless($task->isRunning(), 404);

        FinishTask::dispatch(
            $task, (int) $request->query('exit_code')
        );
    }
}
