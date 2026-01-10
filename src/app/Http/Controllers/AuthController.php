<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use App\Http\Requests\RegisterRequest;

class AuthController extends Controller
{
    public function login()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // 必須カラムの初期値
            'postal_code' => '000',
            'address' => '住所未設定',
        ]);

        // メール認証メールを送信
        $user->sendEmailVerificationNotification();

        // ログインせずにメール認証画面へ（メール認証完了後にログイン）
        $request->session()->put('verification_email', $user->email);

        return redirect()->route('verification.notice');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // ログアウト後はログイン画面にリダイレクト
        return redirect()->route('login');
    }

    public function showVerificationNotice()
    {
        return view('auth.verify-email-notice');
    }

    public function resendVerificationEmail(Request $request)
    {
        // ログインしている場合はユーザーを取得、していない場合はセッションからメールアドレスを取得
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('items.index');
            }
        } else {
            $email = $request->session()->get('verification_email');
            if (!$email) {
                return redirect()->route('login')->with('error', 'メールアドレスが見つかりませんでした。再度ログインしてください。');
            }
            $user = User::where('email', $email)->first();
            if (!$user) {
                return redirect()->route('login')->with('error', 'ユーザーが見つかりませんでした。');
            }
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('login')->with('message', 'メール認証は既に完了しています。ログインしてください。');
            }
        }

        $user->sendEmailVerificationNotification();

        return back()->with('message', '認証メールを再送信しました');
    }

    public function verifyEmail(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if ($user->hasVerifiedEmail()) {
            // 既に認証済みの場合はログイン状態にしてからリダイレクト
            if (!Auth::check()) {
                Auth::login($user);
            }
            // セッションからメールアドレスを削除し、プロフィール編集画面にリダイレクト
            $request->session()->forget('verification_email');
            return redirect()->route('user.edit');
        }

        if ($user->markEmailAsVerified()) {
            // メール認証完了イベントを発行
            event(new Verified($user));
        }

        // メール認証完了後、ログイン状態にする
        Auth::login($user);

        // セッションからメールアドレスを削除し、プロフィール編集画面にリダイレクト
        $request->session()->forget('verification_email');
        return redirect()->route('user.edit');
    }
}
