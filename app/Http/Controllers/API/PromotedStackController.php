<?php

namespace App\Http\Controllers\API;

use App\Stack;
use App\Environment;
use Illuminate\Http\Request;
use App\Rules\StackIsPromotable;
use App\Http\Controllers\Controller;

class PromotedStackController extends Controller
{
    /**
     * Get the promoted stack for the environment.
     *
     * @param  Request  $request
     * @param  \App\Environment  $environment
     * @return mixed
     */
    public function show(Request $request, Environment $environment)
    {
        $this->authorize('view', $environment->project);

        if (! $environment->promotedStack()) {
            abort(404);
        }

        return $environment->promotedStack();
    }

    /**
     * Set the promoted stack for the environment.
     *
     * @param  Request  $request
     * @param  \App\Environment  $environment
     * @return mixed
     */
    public function update(Request $request, Environment $environment)
    {
        $request->validate([
            'stack' => [
                'required',
                new StackIsPromotable($stack = Stack::findOrFail($request->stack))
            ],
            'hooks' => 'nullable|boolean',
            'wait' => 'nullable|boolean',
        ]);

        $this->authorize('view', $stack);

        if (! $environment->promotionLock()->get()) {
            return response()->json([
                'environment' => ['This environment is already promoting another stack.'],
            ], 409);
        }

        $environment->promote($stack, [
            'hooks' => (bool) $request->hooks,
            'wait' => (bool) $request->wait,
        ]);
    }
}
