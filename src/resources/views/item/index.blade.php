@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/index.css') }}">
@endsection

@section('content')
<div class="items__content">
    <!-- タブセクション -->
    <div class="items__tabs">
        <a href="{{ route('items.index', array_merge(['tab' => 'recommended'], request('search') ? ['search' => request('search')] : [])) }}" class="items__tab {{ $activeTab === 'recommended' ? 'items__tab--active' : '' }}">
            おすすめ
        </a>
        <a href="{{ route('items.index', array_merge(['tab' => 'mylist'], request('search') ? ['search' => request('search')] : [])) }}" class="items__tab {{ $activeTab === 'mylist' ? 'items__tab--active' : '' }}">
            マイリスト
        </a>
    </div>

    <!-- 商品一覧セクション -->
    <div class="items__grid-container">
        @if($items->count() > 0)
            <div class="items__grid">
                @foreach($items as $item)
                    <div class="item__card">
                        <a href="{{ route('items.detail', ['item_id' => $item->id]) }}" class="item__link">
                            <div class="item__image">
                                @if($item->image_path)
                                    <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}">
                                @else
                                    <div class="item__image-placeholder">商品画像</div>
                                @endif
                                @if($item->is_sold === 'sold' || $item->is_sold === 'pending')
                                    <div class="item__sold-badge">{{ $item->is_sold === 'pending' ? '入金待ち' : 'Sold' }}</div>
                                @endif
                            </div>
                            <div class="item__name">{{ $item->name }}</div>
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <div class="items__empty">
                @if($activeTab === 'mylist' && !Auth::check())
                    <p>ログインすると、いいねした商品がここに表示されます</p>
                @elseif($activeTab === 'mylist')
                    <p>いいねした商品がありません</p>
                @else
                    <p>商品がありません</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection

