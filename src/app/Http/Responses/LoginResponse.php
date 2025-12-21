<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        $user = $request->user();

        // メール認証が完了していない場合は認証メールを送信してからログアウトして誘導画面へ
        if (!$user->hasVerifiedEmail()) {
            $email = $user->email;
            $user->sendEmailVerificationNotification();

            // ログアウトしてメール認証画面へ
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // メールアドレスをセッションに保存（再送信時に使用）
            $request->session()->put('verification_email', $email);

            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('items.index'));
    }
}

