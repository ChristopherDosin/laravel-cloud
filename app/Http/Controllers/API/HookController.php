<?php

namespace App\Http\Controllers\API;

use App\Hook;
use App\Stack;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateHookRequest;
use Illuminate\Validation\ValidationException;

class HookController extends Controller
{
    /**
     * Get the hooks for the given environment / stack.
     *
     * @param  Request  $request
     * @param  \App\Stack  $stack
     * @return Response
     */
    public function index(Request $request, Stack $stack)
    {
        $this->authorize('view', $stack->project());

        return $stack->hooks->load('stack');
    }

    /**
     * Create a new hook for the stack.
     *
     * @param  \App\Http\Requests\CreateHookRequest  $request
     * @param  \App\Stack  $stack
     * @return Response
     */
    public function store(CreateHookRequest $request, Stack $stack)
    {
        $this->authorize('view', $stack->project());

        if (! $stack->isProvisioned()) {
            throw ValidationException::withMessages([
                'stack' => ['This stack has not finished provisioning.'],
            ]);
        }

        $hook = DB::transaction(function () use ($request, $stack) {
            return tap($stack->hooks()->create([
                'name' => $request->name,
                'token' => Str::random(40),
                'branch' => $request->branch,
                'meta' => [],
            ]), function ($hook) use ($request) {
                if ($request->publish) {
                    $hook->publish();
                }
            });
        });

        return response()->json($hook, 201);
    }

    /**
     * Delete the given hook.
     *
     * @param  \App\Hook  $hook
     * @return Response
     */
    public function destroy(Hook $hook)
    {
        $this->authorize('view', $hook->project());

        $hook->delete();
    }
}
