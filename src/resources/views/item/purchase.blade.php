@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
<div class="purchase__content">
    @if(session('error'))
        <div class="purchase__alert purchase__alert--error">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="purchase__alert purchase__alert--success">{{ session('success') }}</div>
    @endif

    <div class="purchase__main">
        <!-- 左側：商品情報と入力フィールド -->
        <div class="purchase__left">
            <!-- 商品情報 -->
            <div class="purchase__product-section">
                <div class="purchase__product-image">
                    @if($item->image_path)
                        <img src="{{ asset('storage/' . $item->image_path) }}" alt="{{ $item->name }}">
                    @else
                        <div class="purchase__image-placeholder">商品画像</div>
                    @endif
                </div>
                <div class="purchase__product-info">
                    <div class="purchase__product-name">商品名</div>
                    <div class="purchase__product-title">{{ $item->name }}</div>
                    <div class="purchase__product-price">¥ {{ number_format($item->price) }}</div>
                </div>
            </div>

            <!-- 支払い方法 -->
            <div class="purchase__section">
                <h2 class="purchase__section-title">支払い方法</h2>
                <form id="purchase-form" action="{{ route('items.purchase', ['item_id' => $item->id]) }}" method="post">
                    @csrf
                    <select name="payment_method_id" id="payment_method" class="purchase__select" required>
                        <option value="">選択してください</option>
                        @foreach($paymentMethods as $paymentMethod)
                            <option value="{{ $paymentMethod->id }}">{{ $paymentMethod->name }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <!-- 配送先 -->
            <div class="purchase__section">
                <h2 class="purchase__section-title">配送先</h2>
                <div class="purchase__shipping-info">
                    <div class="purchase__shipping-address">
                        <div>〒 {{ $shippingPostalCode }}</div>
                        <div>{{ $shippingAddress }}</div>
                        @if($shippingBuildingName)
                            <div>{{ $shippingBuildingName }}</div>
                        @endif
                    </div>
                    <a href="{{ route('shipping.show', ['item_id' => $item->id]) }}" class="purchase__change-link">変更する</a>
                </div>
                <div class="purchase__divider"></div>
            </div>
        </div>

        <!-- 右側：注文サマリー -->
        <div class="purchase__right">
            <div class="purchase__summary">
                <div class="purchase__summary-row">
                    <span class="purchase__summary-label">商品代金</span>
                    <span class="purchase__summary-value">¥{{ number_format($item->price) }}</span>
                </div>
                <div class="purchase__summary-row">
                    <span class="purchase__summary-label">支払い方法</span>
                    <span class="purchase__summary-value" id="selected-payment">-</span>
                </div>
            </div>
            <button type="submit" form="purchase-form" class="purchase__button">購入する</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentSelect = document.getElementById('payment_method');
    const selectedPayment = document.getElementById('selected-payment');
    const purchaseForm = document.getElementById('purchase-form');
    const purchaseButton = document.querySelector('.purchase__button');
    const paymentMethods = {
        @foreach($paymentMethods as $paymentMethod)
        '{{ $paymentMethod->id }}': '{{ $paymentMethod->name }}',
        @endforeach
    };

    paymentSelect.addEventListener('change', function() {
        const selectedId = this.value;
        if (selectedId && paymentMethods[selectedId]) {
            selectedPayment.textContent = paymentMethods[selectedId];
        } else {
            selectedPayment.textContent = '-';
        }
    });

    // フォーム送信をインターセプトしてAJAXで処理
    if (purchaseForm) {
        purchaseForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(purchaseForm);
            const submitButton = purchaseButton;

            // ボタンを無効化
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = '処理中...';
            }

            fetch(purchaseForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.url) {
                    // Stripe決済画面を新規タブで開く（コンビニ支払い・カード支払い共通）
                    const stripeWindow = window.open(data.url, '_blank');

                    // コンビニ支払いの場合、入金待ち状態にする
                    if (data.is_konbini && data.is_pending) {
                        if (submitButton) {
                            submitButton.disabled = true;
                            submitButton.textContent = '入金待ち';
                        }
                        return;
                    }

                    // カード支払いの場合、Stripe画面が閉じられたかどうかを監視
                    // ただし、決済完了メッセージを受け取った場合は処理しない
                    const checkClosed = setInterval(function() {
                        if (stripeWindow && stripeWindow.closed) {
                            clearInterval(checkClosed);
                            // メッセージで完了処理されていない場合のみリセット
                            if (submitButton && submitButton.disabled && submitButton.textContent === '処理中...') {
                                submitButton.disabled = false;
                                submitButton.textContent = '購入する';
                            }
                        }
                    }, 1000);
                } else {
                    alert(data.message || 'エラーが発生しました');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = '購入する';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('エラーが発生しました');
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = '購入する';
                }
            });
        });
    }

    // 購入完了メッセージを受け取るリスナー
    window.addEventListener('message', function(event) {
        // セキュリティのため、同じオリジンからのメッセージのみ処理
        if (event.origin !== window.location.origin) {
            return;
        }

        if (event.data && event.data.type === 'purchase-success') {
            // カード支払いの場合、決済完了時にボタンを購入済みにして非活性にする
            if (event.data.is_completed && purchaseButton) {
                purchaseButton.disabled = true;
                purchaseButton.textContent = '購入済み';
            }
            // 商品一覧にリダイレクト
            window.location.href = event.data.redirectUrl;
        }
    });
});
</script>
@endsection

