<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    /**
     * Handle an API login attempt.
     *
     * @return Response
     */
    public function handle()
    {
        $user = User::where('email', request('email'))->firstOrFail();

        if (! Hash::check(request('password'), $user->password)) {
            abort(422);
        }

        $user->revokeTokens(request('host'));

        return ['access_token' => $user->createToken(request('host'), ['*'])->accessToken];
    }
}
