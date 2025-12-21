<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\SoldItem;

class PurchaseService
{
    /**
     * 入金待ち状態にする（コンビニ支払い用）
     *
     * @param int $item_id
     * @param array $pendingPurchase
     * @param string|null $stripeSessionId
     * @return void
     * @throws \Exception
     */
    public function pendingPurchase(int $item_id, array $pendingPurchase, ?string $stripeSessionId = null)
    {
        DB::beginTransaction();
        try {
            $item = Item::findOrFail($item_id);
            SoldItem::create([
                'item_id' => $item->id,
                'user_id' => Auth::id(),
                'payment_method_id' => $pendingPurchase['payment_method_id'],
                'shipping_postal_code' => $pendingPurchase['shipping_postal_code'],
                'shipping_address' => $pendingPurchase['shipping_address'],
                'shipping_building_name' => $pendingPurchase['shipping_building_name'],
                'stripe_session_id' => $stripeSessionId,
            ]);
            $item->update(['is_sold' => 'pending']);
            $this->clearPurchaseSession();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 購入処理を完了
     *
     * @param int $item_id
     * @param array $pendingPurchase
     * @param string|null $stripeSessionId
     * @return void
     * @throws \Exception
     */
    public function completePurchase(int $item_id, array $pendingPurchase, ?string $stripeSessionId = null)
    {
        DB::beginTransaction();
        try {
            $item = Item::findOrFail($item_id);
            SoldItem::create([
                'item_id' => $item->id,
                'user_id' => Auth::id(),
                'payment_method_id' => $pendingPurchase['payment_method_id'],
                'shipping_postal_code' => $pendingPurchase['shipping_postal_code'],
                'shipping_address' => $pendingPurchase['shipping_address'],
                'shipping_building_name' => $pendingPurchase['shipping_building_name'],
                'stripe_session_id' => $stripeSessionId,
            ]);
            $item->update(['is_sold' => 'sold']);
            $this->clearPurchaseSession();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * 購入処理を取消
     *
     * @param int $item_id
     * @return void
     */
    public function cancelPurchase(int $item_id)
    {
        DB::beginTransaction();
        try {
            $item = Item::findOrFail($item_id);

            // SoldItemを削除（最新の購入レコードを削除）
            SoldItem::where('item_id', $item_id)
                ->where('user_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->first()
                ?->delete();

            // 商品を未売りに戻す
            $item->update(['is_sold' => null]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    /**
     * 購入セッションを保存
     *
     * @param string $sessionId
     * @param int $item_id
     * @param int $paymentMethodId
     * @param string $shippingPostalCode
     * @param string $shippingAddress
     * @param string|null $shippingBuildingName
     * @return void
     */
    public function savePendingPurchase(string $sessionId, int $item_id, int $paymentMethodId, string $shippingPostalCode, string $shippingAddress, ?string $shippingBuildingName)
    {
        session(['stripe_session_id' => $sessionId]);
        session(['pending_purchase' => [
            'item_id' => $item_id,
            'payment_method_id' => $paymentMethodId,
            'shipping_postal_code' => $shippingPostalCode,
            'shipping_address' => $shippingAddress,
            'shipping_building_name' => $shippingBuildingName,
        ]]);
    }

    /**
     * 購入セッションをクリア
     *
     * @return void
     */
    public function clearPurchaseSession()
    {
        session()->forget(['stripe_session_id', 'pending_purchase', 'shipping_postal_code', 'shipping_address', 'shipping_building_name']);
    }
}

