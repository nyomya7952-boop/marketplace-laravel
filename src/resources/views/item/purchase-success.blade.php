@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/purchase.css') }}">
@endsection

@section('content')
<div class="purchase__content">
    <div class="purchase__success-message">
        <h1>購入が完了しました</h1>
        <p>ありがとうございます。商品の購入が正常に完了しました。
            5秒後に自動的にタブを閉じて商品一覧に戻ります。
        </p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 5秒待機してから処理を実行
    setTimeout(function() {
        // 新しいタブから開かれた場合（window.openerが存在する場合）
        if (window.opener && !window.opener.closed) {
            // 親ウィンドウにメッセージを送信して商品一覧にリダイレクト
            window.opener.postMessage({
                type: 'purchase-success',
                redirectUrl: '{{ route('items.index') }}',
                is_completed: true // カード支払いの場合、決済完了済み
            }, window.location.origin);

            // このタブを閉じる
            window.close();
        } else {
            // window.openerが存在しない場合（直接アクセスされた場合など）は通常のリダイレクト
            window.location.href = '{{ route('items.index') }}';
        }
    }, 5000); // 5秒（5000ミリ秒）待機
});
</script>
@endsection

