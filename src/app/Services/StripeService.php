<?php

namespace App\Services;

use App\Models\Item;
use App\Models\MasterData;

class StripeService
{
    /**
     * Stripe Checkoutセッションを作成
     *
     * @param Item $item
     * @param MasterData $paymentMethod
     * @param int $item_id
     * @return \Stripe\Checkout\Session
     */
    public function createCheckoutSession(Item $item, MasterData $paymentMethod, int $item_id)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        return \Stripe\Checkout\Session::create([
            'payment_method_types' => $paymentMethod->name === 'カード支払い' ? ['card'] : ['konbini'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'jpy',
                    'product_data' => [
                        'name' => $item->name,
                    ],
                    'unit_amount' => $item->price,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('items.purchase.success', ['item_id' => $item_id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('items.purchase.show', ['item_id' => $item_id]),
        ]);
    }

    /**
     * Stripe Checkoutセッションを取得
     *
     * @param string $sessionId
     * @return \Stripe\Checkout\Session
     */
    public function retrieveCheckoutSession(string $sessionId)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        return \Stripe\Checkout\Session::retrieve($sessionId);
    }

    /**
     * 決済ステータスが完了可能かどうかを判定
     *
     * @param \Stripe\Checkout\Session $session
     * @param MasterData|null $paymentMethod
     * @return bool
     */
    public function canCompletePayment($session, $paymentMethod)
    {
        $isKonbini = $paymentMethod && $paymentMethod->name === 'コンビニ支払い';

        return $session->payment_status === 'paid' || ($isKonbini && $session->payment_status === 'unpaid');
    }
}

