<?php

namespace App\Http\Controllers\API;

use App\Database;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class DatabaseTransferController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  Request  $request
     * @param  \App\Database  $database
     * @return mixed
     */
    public function store(Request $request, Database $database)
    {
        $this->authorize('transfer', $database);

        $request->validate([
            'project_id' => [
                'required' ,
                'integer',
                Rule::exists('projects', 'id')->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                })
            ],
        ]);

        $database->stacks()->detach();

        tap($database)->update([
            'project_id' => $request->project_id,
        ])->syncNetwork();
    }
}
