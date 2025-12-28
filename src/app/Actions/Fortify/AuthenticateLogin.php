<?php

namespace App\Actions\Fortify;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticateLogin
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\User|null
     */
    public function __invoke(Request $request)
    {
        $user = Auth::guard('web')->getProvider()->retrieveByCredentials([
            'email' => $request->email,
        ]);

        if ($user && Auth::guard('web')->getProvider()->validateCredentials($user, ['password' => $request->password])) {
            return $user;
        }

        throw ValidationException::withMessages([
            'email' => ['ログイン情報が登録されていません'],
        ]);
    }
}

