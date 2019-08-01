<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class OwnedProjectsController extends Controller
{
    /**
     * Get all of the owned projects for the current user.
     *
     * @param  Request  $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $request->user()->projects->sortBy('name')->reject->archived;
    }
}
