@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/detail.css') }}">
@endsection

@section('content')
<div class="detail__content">
    <div class="detail__main">
        <!-- 左側：商品画像 -->
        <div class="detail__image-section">
            <div class="detail__image">
                @if($item->image_path)
                    <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}">
                @else
                    <div class="detail__image-placeholder">商品画像</div>
                @endif
            </div>
        </div>

        <!-- 右側：商品情報 -->
        <div class="detail__info-section">
            <!-- 商品名・ブランド名・価格 -->
            <div class="detail__header">
                <h1 class="detail__title">{{ $item->name }}</h1>
                @if($item->brand)
                    <p class="detail__brand">{{ $item->brand->name }}</p>
                @endif
                <p class="detail__price">¥{{ number_format($item->price) }} (税込)</p>

                <!-- いいね数・コメント数 -->
                <div class="detail__stats">
                    <span class="detail__stat">
                        @auth
                            <button type="button" class="detail__like-button {{ $isLiked ? 'detail__like-button--liked' : '' }}" data-item-id="{{ $item->id }}">
                                <span class="detail__stat-icon">♥</span>
                                <span class="detail__stat-count">{{ $item->likes->count() }}</span>
                            </button>
                        @else
                            <a href="{{ route('login') }}" class="detail__like-link">
                                <span class="detail__stat-icon">♥</span>
                                <span class="detail__stat-count">{{ $item->likes->count() }}</span>
                            </a>
                        @endauth
                    </span>
                    <span class="detail__stat">
                        <span class="detail__stat-icon">💬</span>
                        <span class="detail__stat-count">{{ $item->comments->count() }}</span>
                    </span>
                </div>

                <!-- 購入手続きボタン -->
                @if($item->is_sold === null || $item->is_sold === false)
                    @auth
                        @if($item->user_id !== Auth::id())
                            <a href="{{ route('items.purchase.show', ['item_id' => $item->id]) }}" class="detail__purchase-button">購入手続きへ</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="detail__purchase-button">購入手続きへ</a>
                    @endauth
                @elseif($item->is_sold === 'pending')
                    <div class="detail__sold-message">入金待ち</div>
                @else
                    <div class="detail__sold-message">この商品は売り切れです</div>
                @endif
            </div>

            <!-- 商品説明 -->
            <div class="detail__section">
                <h2 class="detail__section-title">商品説明</h2>
                <div class="detail__description">
                    {!! nl2br(e($item->description)) !!}
                </div>
            </div>

            <!-- 商品の情報 -->
            <div class="detail__section">
                <h2 class="detail__section-title">商品の情報</h2>
                <div class="detail__info">
                    <!-- カテゴリ -->
                    <div class="detail__info-item">
                        <span class="detail__info-label">カテゴリー</span>
                        <div class="detail__categories">
                            @foreach($item->categories as $category)
                                <span class="detail__category-tag">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    </div>
                    <!-- 商品の状態 -->
                    @if($condition)
                        <div class="detail__info-item">
                            <span class="detail__info-label">商品の状態</span>
                            <span class="detail__info-value">{{ $condition->name }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- コメントセクション -->
    <div class="detail__comments-section">
        <h2 class="detail__section-title">コメント({{ $item->comments->count() }})</h2>

        <!-- 既存のコメント -->
        @if($item->comments->count() > 0)
            <div class="detail__comments-list">
                @foreach($item->comments as $comment)
                    <div class="detail__comment">
                        <div class="detail__comment-user">
                            <div class="detail__comment-avatar">
                                @if($comment->user->profile_image_path)
                                    <img src="{{ asset('storage/' . $comment->user->profile_image_path) }}" alt="{{ $comment->user->name }}">
                                @else
                                    <div class="detail__comment-avatar-placeholder">{{ mb_substr($comment->user->name, 0, 1) }}</div>
                                @endif
                            </div>
                            <span class="detail__comment-username">{{ $comment->user->name }}</span>
                        </div>
                        <div class="detail__comment-content">
                            {!! nl2br(e($comment->content)) !!}
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- コメント投稿フォーム -->
        <div class="detail__comment-form">
            <h3 class="detail__section-title">商品へのコメント</h3>
            <form id="comment-form" action="{{ route('items.comment', ['item_id' => $item->id]) }}" method="post">
                @csrf
                <textarea name="content" class="detail__comment-textarea" rows="5" placeholder="コメントを入力してください"></textarea>
                @error('content')
                    <div class="detail__error">
                        @if($message === 'コメントするにはログインしてください')
                            コメントするには<a href="{{ route('login') }}">ログイン</a>してください
                        @else
                            {{ $message }}
                        @endif
                    </div>
                @enderror
                <button type="submit" class="detail__comment-submit">コメントを送信する</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // いいねボタンの処理
    const likeButton = document.querySelector('.detail__like-button');

    if (likeButton) {
        likeButton.addEventListener('click', function() {
            const itemId = this.getAttribute('data-item-id');
            const countElement = this.querySelector('.detail__stat-count');
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            if (!csrfToken) {
                alert('CSRFトークンが見つかりません');
                return;
            }

            // ボタンを無効化（連続クリック防止）
            this.disabled = true;

            fetch(`/item/${itemId}/like`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'エラーが発生しました');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // いいね状態を更新
                    if (data.isLiked) {
                        this.classList.add('detail__like-button--liked');
                    } else {
                        this.classList.remove('detail__like-button--liked');
                    }

                    // いいね数を更新
                    countElement.textContent = data.likeCount;
                } else {
                    alert(data.message || 'エラーが発生しました');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message || 'エラーが発生しました');
            })
            .finally(() => {
                // ボタンを再有効化
                this.disabled = false;
            });
        });
    }

});
</script>
@endsection

