@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
<div class="login-form__content">
    <div class="verify-email__message">
        <p>登録していただいたメールアドレスに認証メールを送付しました。</p>
        <p>メール認証を完了してください。</p>
    </div>

    @if (session('message'))
        <div class="verify-email__success">
            {{ session('message') }}
        </div>
    @endif

    <div class="verify-email__actions">
        @php
            // ログインしている場合はユーザーを取得、していない場合はセッションからメールアドレスで取得
            if (Auth::check()) {
                $user = Auth::user();
            } else {
                $email = session('verification_email');
                $user = $email ? \App\Models\User::where('email', $email)->first() : null;
            }
        @endphp
        @if($user)
                <a href="http://localhost:8025" target="_blank" class="verify-email__button">
                    認証はこちらから
                </a>
                <br>
        @endif

        <div style="margin-top: 20px;">
            <form action="{{ route('verification.resend') }}" method="post" style="display: inline;">
                @csrf
                <button type="submit" class="verify-email__resend-link">認証メールを再送する</button>
            </form>
        </div>
    </div>
</div>
@endsection

