<?php

namespace App\Http\Controllers\API;

use App\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StackDatabaseController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function update(Request $request)
    {
        $this->authorize('view', $request->stack->project());

        $request->validate([
            'databases' => 'array',
            'databases.*' => 'string',
        ]);

        $databases = $request->stack->project()->databases()->with('stacks')->get();

        $databases->reject(function ($database) use ($request) {
            return ((in_array($database->name, $request->databases) &&
                     $database->stacks->contains($request->stack)) ||
                   (! in_array($database->name, $request->databases) &&
                    ! $database->stacks->contains($request->stack)));
        })->each(function ($database) use ($request) {
            $database->stacks()->toggle($request->stack);

            $database->syncNetwork();
        });
    }
}
