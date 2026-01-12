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
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        // Webhookシークレットが設定されていない場合のエラーチェック
        if (empty($endpointSecret)) {
            Log::error('Stripe Webhook: Webhook secret is not configured', [
                'config_value' => config('services.stripe.webhook_secret'),
                'env_value' => env('STRIPE_WEBHOOK_SECRET')
            ]);
            return response()->json(['error' => 'Webhook secret is not configured'], 500);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature', [
                'error' => $e->getMessage(),
                'has_signature_header' => !empty($sigHeader),
                'has_webhook_secret' => !empty($endpointSecret)
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        try {
            // checkout.session.async_payment_succeededイベントを処理（コンビニ支払いの入金完了時）
            // カード支払いの処理は purchaseSuccess メソッドで行われるため、ここでは何もしない
            if ($event->type === 'checkout.session.async_payment_succeeded') {
                $session = $event->data->object;
                $this->handleAsyncPaymentSucceeded($session);
            }
        } catch (\Throwable $e) {
            // ここで落ちるとStripe側がリトライし続けるため、原因を必ずログに残す
            Log::error('Stripe Webhook: Handler failed', [
                'event_type' => $event->type ?? null,
                'event_id' => $event->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Webhook handler failed'], 500);
        }

        return response()->json(['received' => true]);
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

        // セッションIDが見つからない場合はエラー
        if (!$soldItem) {
            Log::error('Stripe Webhook: SoldItem not found for session', [
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
                'payment_method' => $paymentMethod?->name,
                'item_status' => $item->is_sold
            ]);
        }
    }
}
