@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile.css') }}">
@endsection

@section('content')
<div class="profile__content">
    <!-- プロフィール情報セクション -->
    <div class="profile__header">
        <div class="profile__image">
            <img src="{{ $user->profile_image_path ? asset('storage/' . $user->profile_image_path) : asset('storage/images/profile.jpg') }}" alt="プロフィール画像">
        </div>
        <div class="profile__info">
            <h2 class="profile__name">{{ $user->name }}</h2>
            <a href="{{ route('user.edit') }}" class="profile__edit-button">プロフィールを編集</a>
        </div>
    </div>

    <!-- タブセクション -->
    <div class="profile__tabs">
        <a href="{{ route('user.profile', ['page' => 'sell']) }}" class="profile__tab {{ $activeTab === 'sell' ? 'profile__tab--active' : '' }}">
            出品した商品
        </a>
        <a href="{{ route('user.profile', ['page' => 'buy']) }}" class="profile__tab {{ $activeTab === 'buy' ? 'profile__tab--active' : '' }}">
            購入した商品
        </a>
    </div>

    <!-- 商品一覧セクション -->
    <div class="profile__items">
        @if($activeTab === 'sell')
            @if($soldItems->count() > 0)
                <div class="items__grid">
                    @foreach($soldItems as $item)
                        <div class="item__card">
                            <a href="{{ route('items.detail', ['item_id' => $item->id]) }}" class="item__link">
                                <div class="item__image">
                                    @if($item->image_path)
                                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}">
                                    @else
                                        <div class="item__image-placeholder">商品画像</div>
                                    @endif
                                </div>
                                <div class="item__name">{{ $item->name }}</div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="items__empty">
                    <p>出品した商品がありません</p>
                </div>
            @endif
        @else
            @if($purchasedItems->count() > 0)
                <div class="items__grid">
                    @foreach($purchasedItems as $item)
                        @if($item)
                            <div class="item__card">
                                <a href="{{ route('items.detail', ['item_id' => $item->id]) }}" class="item__link">
                                    <div class="item__image">
                                        @if($item->image_path)
                                            <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}">
                                        @else
                                            <div class="item__image-placeholder">商品画像</div>
                                        @endif
                                    </div>
                                    <div class="item__name">{{ $item->name }}</div>
                                </a>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="items__empty">
                    <p>購入した商品がありません</p>
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
