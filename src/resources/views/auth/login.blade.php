@extends('layouts.app')
@section('title', 'ログイン')
@section('css')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endsection

@section('content')
    <div class="login__container">
        <div class="login-title">
            <h2>ログイン</h2>
        </div>
        <form class="login-form" action="/login" method="post">
            @csrf
            <div class="login-form__group">
                <div class="login-form__title">メールアドレス</div>
                <div class="login-form__content">
                    <div class="login-form__input">
                        <input type="email" name="email" placeholder="test@example.com" value="{{ old('email') }}" />
                    <div class="login-form__error">
                        @error('email')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
            </div>
            <div class="login-form__group">
                <div class="login-form__title">パスワード</div>
                <div class="login-form__content">
                    <div class="login-form__input">
                        <input type="password" name="password" placeholder="パスワード" value="" />
                    </div>
                    <div class="login-form__error">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </div>
                </div>
            </div>
            <div class="login-form__button">
                <button class="login-form__button-submit" type="submit">ログイン</button>
            </div>
        </form>
    </div>
@endsection