<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Item;
use App\Models\SoldItem;
use App\Models\MasterData;
use App\Services\StripeService;

class StripeWebhookController extends Controller
{
    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Stripe Webhookを処理
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');
        $endpoint_secret = config('services.stripe.webhook_secret');

        // Webhookシークレットが設定されていない場合のエラーチェック
        if (empty($endpoint_secret)) {
            Log::error('Stripe Webhook: Webhook secret is not configured', [
                'config_value' => config('services.stripe.webhook_secret'),
                'env_value' => env('STRIPE_WEBHOOK_SECRET')
            ]);
            return response()->json(['error' => 'Webhook secret is not configured'], 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sig_header,
                $endpoint_secret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature', [
                'error' => $e->getMessage(),
                'has_signature_header' => !empty($sig_header),
                'has_webhook_secret' => !empty($endpoint_secret)
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // checkout.session.completedイベントを処理（カード支払い用）
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $this->handleCheckoutSessionCompleted($session);
        }

        // checkout.session.async_payment_succeededイベントを処理（コンビニ支払いの入金完了時）
        if ($event->type === 'checkout.session.async_payment_succeeded') {
            $session = $event->data->object;
            $this->handleAsyncPaymentSucceeded($session);
        }

        return response()->json(['received' => true]);
    }

    /**
     * checkout.session.completedイベントを処理（カード支払い用）
     *
     * @param \Stripe\Checkout\Session $session
     * @return void
     */
    private function handleCheckoutSessionCompleted($session)
    {
        // カード支払いの場合は、payment_statusがpaidの時点で完了している
        // コンビニ支払いの場合は、この時点では入金待ちなので処理しない
        // カード支払いの処理は purchaseSuccess メソッドで行われるため、ここでは何もしない
    }

    /**
     * checkout.session.async_payment_succeededイベントを処理（コンビニ支払いの入金完了時）
     *
     * @param \Stripe\Checkout\Session $session
     * @return void
     */
    private function handleAsyncPaymentSucceeded($session)
    {
        // セッションIDからSoldItemを検索
        $soldItem = SoldItem::where('stripe_session_id', $session->id)->first();

        // セッションIDが見つからない場合のフォールバック処理
        // （テストイベントや、何らかの理由でセッションIDが一致しない場合）
        if (!$soldItem) {
            // コンビニ支払いのpayment_method_idを取得
            $konbiniPaymentMethod = MasterData::where('type', 'payment_method')
                ->where('name', 'コンビニ支払い')
                ->first();

            if (!$konbiniPaymentMethod) {
                Log::error('Stripe Webhook: Konbini payment method not found');
                return;
            }

            // 入金待ちの商品を取得（コンビニ支払いのみ）
            $pendingSoldItems = SoldItem::where('payment_method_id', $konbiniPaymentMethod->id)
                ->whereHas('item', function($query) {
                    $query->where('is_sold', 'pending');
                })
                ->with('item')
                ->get();

            if ($pendingSoldItems->isEmpty()) {
                Log::warning('Stripe Webhook: No pending items found for session', [
                    'session_id' => $session->id
                ]);
                return;
            }

            // 最初の入金待ち商品を購入済みに更新
            $soldItem = $pendingSoldItems->first();
            $item = $soldItem->item;

            if (!$item) {
                Log::error('Stripe Webhook: Item not found for sold_item', [
                    'sold_item_id' => $soldItem->id,
                    'item_id' => $soldItem->item_id
                ]);
                return;
            }

            $item->update(['is_sold' => 'sold']);
            Log::info('Stripe Webhook: Payment completed for item', [
                'item_id' => $item->id,
                'session_id' => $session->id
            ]);
            return;
        }

        // セッションIDで見つかった場合の通常処理
        $item = Item::find($soldItem->item_id);
        if (!$item) {
            Log::warning('Stripe Webhook: Item not found', ['item_id' => $soldItem->item_id]);
            return;
        }

        $paymentMethod = MasterData::find($soldItem->payment_method_id);

        // コンビニ支払いで入金待ち状態の場合のみ処理
        if ($paymentMethod && $paymentMethod->name === 'コンビニ支払い' && $item->is_sold === 'pending') {
            $item->update(['is_sold' => 'sold']);
            Log::info('Stripe Webhook: Payment completed for item', [
                'item_id' => $item->id,
                'session_id' => $session->id
            ]);
        } else {
            Log::warning('Stripe Webhook: Conditions not met for item', [
                'item_id' => $item->id,
                'payment_method' => $paymentMethod->name ?? null,
                'item_status' => $item->is_sold
            ]);
        }
    }
}
