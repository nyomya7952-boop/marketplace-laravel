@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
<div class="purchase__content">
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
                <form id="purchase-form" action="{{ route('items.purchase', ['item_id' => $item->id]) }}" method="post" novalidate>
                    @csrf
                    <select name="payment_method_id" id="payment_method" class="purchase__select" required>
                        <option value="">選択してください</option>
                        @foreach($paymentMethods as $paymentMethod)
                            <option value="{{ $paymentMethod->id }}">{{ $paymentMethod->name }}</option>
                        @endforeach
                    </select>
                    <div class="purchase__error">
                        @error('payment_method_id')
                        {{ $message }}
                        @enderror
                    </div>
                </form>
            </div>

            <!-- 配送先 -->
            <div class="purchase__section">
                <h2 class="purchase__section-title">配送先</h2>
                <div class="purchase__shipping-info">
                    <div class="purchase__shipping-address">
                        <div>〒 {{ $shippingPostalCode === '000' ? '' : $shippingPostalCode }}</div>
                        <div>{{ $shippingAddress === '住所未設定' ? '' : $shippingAddress }}</div>
                        @if($shippingBuildingName)
                            <div>{{ $shippingBuildingName }}</div>
                        @endif
                    </div>
                    <a href="{{ route('shipping.show', ['item_id' => $item->id]) }}" class="purchase__change-link">変更する</a>
                </div>
                <div class="purchase__error" id="shipping-error">
                    @error('shipping')
                    {{ $message }}
                    @enderror
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
    const flashContainer = document.getElementById('flash-container');
    const paymentSelect = document.getElementById('payment_method');
    const selectedPayment = document.getElementById('selected-payment');
    const purchaseForm = document.getElementById('purchase-form');
    const purchaseButton = document.querySelector('.purchase__button');
    const paymentErrorDiv = document.querySelector('.purchase__error');
    const shippingErrorDiv = document.getElementById('shipping-error');
    const paymentMethods = {
        @foreach($paymentMethods as $paymentMethod)
        '{{ $paymentMethod->id }}': '{{ $paymentMethod->name }}',
        @endforeach
    };

    // 支払い方法のエラーメッセージを表示する関数
    function showPaymentError(message) {
        if (paymentErrorDiv) {
            paymentErrorDiv.textContent = message;
            paymentErrorDiv.style.display = 'block';
        }
    }

    // 支払い方法のエラーメッセージをクリアする関数
    function clearPaymentError() {
        if (paymentErrorDiv) {
            paymentErrorDiv.textContent = '';
            paymentErrorDiv.style.display = 'none';
        }
    }

    // 配送先のエラーメッセージを表示する関数
    function showShippingError(message) {
        if (shippingErrorDiv) {
            shippingErrorDiv.textContent = message;
            shippingErrorDiv.style.display = 'block';
        }
    }

    // 配送先のエラーメッセージをクリアする関数
    function clearShippingError() {
        if (shippingErrorDiv) {
            shippingErrorDiv.textContent = '';
            shippingErrorDiv.style.display = 'none';
        }
    }

    // flash領域（partials/flash.blade.php）にエラーを表示
    function showFlashErrors(messages) {
        if (!flashContainer) return;
        const listItems = (messages || [])
            .filter(Boolean)
            .map(msg => `<li class="flash__list-item">${escapeHtml(msg)}</li>`)
            .join('');
        if (!listItems) {
            flashContainer.innerHTML = '';
            return;
        }
        flashContainer.innerHTML = `
            <div class="flash__alert flash__alert--error">
                <ul class="flash__list">
                    ${listItems}
                </ul>
            </div>
        `;
    }

    function clearFlash() {
        if (!flashContainer) return;
        flashContainer.innerHTML = '';
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    paymentSelect.addEventListener('change', function() {
        const selectedId = this.value;
        if (selectedId && paymentMethods[selectedId]) {
            selectedPayment.textContent = paymentMethods[selectedId];
        } else {
            selectedPayment.textContent = '-';
        }
        // 選択が変更されたらエラーメッセージをクリア
        clearPaymentError();
        clearFlash();
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

            // エラーメッセージをクリア
            clearPaymentError();
            clearShippingError();
            clearFlash();

            fetch(purchaseForm.action, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(response => {
                // バリデーションエラー（422）の場合はJSONを読んでcatchへ
                if (response.status === 422) {
                    return response.json().then(data => {
                        throw { isValidationError: true, data: data };
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.url) {
                    // エラーメッセージをクリア
                    clearPaymentError();
                    clearShippingError();
                    clearFlash();

                    // コンビニ支払いの場合、入金待ち状態にして商品一覧にリダイレクト
                    if (data.is_konbini && data.is_pending) {
                        // Stripe決済画面を新規タブで開く（支払い情報確認用）
                        window.open(data.url, '_blank');

                        // 商品一覧画面に自動遷移
                        if (data.redirect_url) {
                            window.location.href = data.redirect_url;
                        } else {
                            // redirect_urlがない場合のフォールバック
                            window.location.href = '{{ route("items.index") }}';
                        }
                        return;
                    }

                    // カード支払いの場合、Stripe決済画面を新規タブで開く
                    const stripeWindow = window.open(data.url, '_blank');

                    // カード支払いの場合、Stripe画面が閉じられたかどうかを監視
                    // ただし、決済完了した場合は処理しない
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
                    const errorMessage = data.message || 'エラーが発生しました';
                    showFlashErrors([errorMessage]);
                    // 既存の表示も維持（配送先/支払い方法のどちらかに寄せる）
                    if (errorMessage.includes('配送先')) {
                        showShippingError(errorMessage);
                    } else {
                        showPaymentError(errorMessage);
                    }

                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = '購入する';
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);

                // バリデーションエラーの場合
                if (error.isValidationError && error.data) {
                    const messages = [];
                    if (error.data.errors) {
                        Object.values(error.data.errors).forEach(arr => {
                            if (Array.isArray(arr)) {
                                arr.forEach(m => messages.push(m));
                            }
                        });
                    }
                    if (messages.length === 0 && error.data.message) {
                        messages.push(error.data.message);
                    }
                    showFlashErrors(messages);

                    // 既存の箇所別エラー表示も残す
                    if (error.data.errors && error.data.errors.shipping) {
                        showShippingError(error.data.errors.shipping[0] || '配送先を設定してください');
                    }
                    if (error.data.errors && error.data.errors.payment_method_id) {
                        showPaymentError(error.data.errors.payment_method_id[0] || '支払方法を選択してください');
                    }
                } else {
                    showFlashErrors(['エラーが発生しました']);
                }

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

