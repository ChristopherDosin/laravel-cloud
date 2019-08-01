<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StackTaskController extends Controller
{
    /**
     * Create a new stack task.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'user' => 'required|string|in:root,cloud',
            'commands' => 'required|array|min:1',
            'commands.*' => 'string',
        ]);

        $request->user === 'root'
                ? $this->authorize('delete', $request->stack)
                : $this->authorize('view', $request->stack);

        return response()->json($request->stack->dispatchTask(
            $request->name, $request->user, $request->commands
        ), 201);
    }
}
