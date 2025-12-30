<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}" />
    @yield('css')
</head>
<body>
    <header class="header">
        <div class="header__inner">
            <a class="header__logo" href="/">
                COACHTECH
            </a>
        </div>
        @php
            $currentRoute = Route::currentRouteName();
            $hideNavRoutes = ['login', 'register', 'verification.notice'];
            $shouldHideNav = in_array($currentRoute, $hideNavRoutes);
        @endphp
        @if(!$shouldHideNav)
        <nav class="header__nav">
            <ul class="header__nav-list">
                <li class="header__nav-item">
                    <form class="header__search-form" action="{{ route('items.index') }}" method="get">
                        <input type="search" name="search" class="header__search-input" placeholder="何をお探しですか？" value="{{ request('search') }}">
                        @if(request('tab'))
                            <input type="hidden" name="tab" value="{{ request('tab') }}">
                        @endif
                    </form>
                </li>
                <li class="header__nav-item">
                    @auth
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <a href="{{ route('logout') }}" class="header__nav-link"
                                onclick="event.preventDefault(); this.closest('form').submit();">ログアウト</a>
                        </form>
                    @endauth
                    @guest
                        <a class="header__nav-link" href="{{ route('login') }}">ログイン</a>
                    @endguest
                </li>
                <li class="header__nav-item">
                    <a class="header__nav-link" href="{{ route('user.profile') }}">マイページ</a>
                </li>
                <li class="header__nav-item">
                    @auth
                        <a class="header__nav-button" href="/sell">出品</a>
                    @else
                        <a class="header__nav-button" href="{{ route('login') }}">出品</a>
                    @endauth
                </li>
            </ul>
        </nav>
        @endif
    </header>

    <main>
        @yield('content')
    </main>
    @yield('js')
</body>
</html>
