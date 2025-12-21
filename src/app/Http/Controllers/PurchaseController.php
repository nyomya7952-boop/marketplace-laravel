<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Item;
use App\Models\MasterData;
use App\Services\StripeService;
use App\Services\PurchaseService;

class PurchaseController extends Controller
{
    protected $stripeService;
    protected $purchaseService;

    public function __construct(StripeService $stripeService, PurchaseService $purchaseService)
    {
        $this->stripeService = $stripeService;
        $this->purchaseService = $purchaseService;
    }
    public function showPurchase($item_id)
    {
        $item = Item::findOrFail($item_id);

        // 購入可能かチェック
        $validationError = $this->validatePurchase($item, $item_id);
        if ($validationError) {
            return $validationError;
        }

        $paymentMethods = MasterData::where('type', 'payment_method')->get();
        $shippingInfo = $this->getShippingInfo();

        return view('item.purchase', [
            'item' => $item,
            'paymentMethods' => $paymentMethods,
            'shippingPostalCode' => $shippingInfo['postal_code'],
            'shippingAddress' => $shippingInfo['address'],
            'shippingBuildingName' => $shippingInfo['building_name'],
        ]);
    }

    private function getShippingInfo()
    {
        $user = Auth::user();
        return [
            'postal_code' => session('shipping_postal_code', $user->postal_code),
            'address' => session('shipping_address', $user->address),
            'building_name' => session('shipping_building_name', $user->building_name),
        ];
    }

    private function validatePurchase($item, $item_id, $expectsJson = false)
    {
        // 既に売り切れまたは入金待ちの場合は商品一覧にリダイレクト
        if ($item->is_sold === 'sold' || $item->is_sold === 'pending') {
            if ($expectsJson) {
                return response()->json(['success' => false, 'message' => 'この商品は既に売り切れです'], 400);
            }
            return redirect()->route('items.index')
                ->with('error', 'この商品は既に売り切れです');
        }

        // 自分が出品した商品の場合は購入できない
        if ($item->user_id === Auth::id()) {
            if ($expectsJson) {
                return response()->json(['success' => false, 'message' => '自分が出品した商品は購入できません'], 400);
            }
            return redirect()->route('items.detail', ['item_id' => $item_id])
                ->with('error', '自分が出品した商品は購入できません');
        }

        return null;
    }

    public function purchase(Request $request, $item_id)
    {
        $request->validate([
            'payment_method_id' => 'required|exists:master_data,id',
        ]);

        $item = Item::findOrFail($item_id);

        // 購入可能かチェック
        $validationError = $this->validatePurchase($item, $item_id, $request->expectsJson());
        if ($validationError) {
            return $validationError;
        }

        $shippingInfo = $this->getShippingInfo();
        $paymentMethod = MasterData::findOrFail($request->payment_method_id);

        // Stripe決済処理
        return $this->processStripePayment($item, $paymentMethod, $item_id, $request->payment_method_id, $shippingInfo['postal_code'], $shippingInfo['address'], $shippingInfo['building_name'], $request->expectsJson());
    }

    private function processStripePayment($item, $paymentMethod, $item_id, $paymentMethodId, $shippingPostalCode, $shippingAddress, $shippingBuildingName, $expectsJson = false)
    {
        $isKonbini = $paymentMethod->name === 'コンビニ支払い';
        $pendingPurchase = [
            'item_id' => $item_id,
            'payment_method_id' => $paymentMethodId,
            'shipping_postal_code' => $shippingPostalCode,
            'shipping_address' => $shippingAddress,
            'shipping_building_name' => $shippingBuildingName,
        ];

        // Stripeセッション作成
        try {
            $checkoutSession = $this->stripeService->createCheckoutSession($item, $paymentMethod, $item_id);
            $this->purchaseService->savePendingPurchase($checkoutSession->id, $item_id, $paymentMethodId, $shippingPostalCode, $shippingAddress, $shippingBuildingName);

            // コンビニ支払いの場合は、先にDB更新（入金待ち状態にする）
            if ($isKonbini) {
                try {
                    $this->purchaseService->pendingPurchase($item_id, $pendingPurchase, $checkoutSession->id);
                } catch (\Exception $e) {
                    if ($expectsJson) {
                        return response()->json([
                            'success' => false,
                            'message' => '購入処理中にエラーが発生しました: ' . $e->getMessage()
                        ], 500);
                    }
                    return redirect()->route('items.purchase.show', ['item_id' => $item_id])
                        ->with('error', '購入処理中にエラーが発生しました: ' . $e->getMessage());
                }
            }

            if ($expectsJson) {
                return response()->json([
                    'success' => true,
                    'url' => $checkoutSession->url,
                    'is_konbini' => $isKonbini,
                    'is_pending' => $isKonbini // コンビニ支払いの場合は入金待ち状態
                ]);
            }

            return redirect($checkoutSession->url);
        } catch (\Exception $e) {
            // Stripeエラーの場合、コンビニ支払いの場合は購入済みを取消
            if ($isKonbini) {
                $this->purchaseService->cancelPurchase($item_id);
            }

            if ($expectsJson) {
                return response()->json([
                    'success' => false,
                    'message' => '決済処理中にエラーが発生しました: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('items.purchase.show', ['item_id' => $item_id])
                ->with('error', '決済処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    public function purchaseSuccess(Request $request, $item_id)
    {
        $sessionId = $request->get('session_id');
        $pendingPurchase = session('pending_purchase');

        if (!$sessionId || !$pendingPurchase || $pendingPurchase['item_id'] != $item_id) {
            return redirect()->route('items.index')
                ->with('error', '無効なリクエストです');
        }

        try {
            $session = $this->stripeService->retrieveCheckoutSession($sessionId);
            $paymentMethod = MasterData::find($pendingPurchase['payment_method_id']);
            $isKonbini = $paymentMethod && $paymentMethod->name === 'コンビニ支払い';

            // コンビニ支払いの場合は既に入金待ち状態なので、成功ページを表示するだけ
            if ($isKonbini) {
                $this->purchaseService->clearPurchaseSession();
                return view('item.purchase-success');
            }

            // カード支払いの場合は決済完了を確認してから購入処理
            if ($session->payment_status === 'paid') {
                $this->purchaseService->completePurchase($item_id, $pendingPurchase, $sessionId);
                return view('item.purchase-success');
            } else {
                return redirect()->route('items.purchase.show', ['item_id' => $item_id])
                    ->with('error', '決済が完了していません');
            }
        } catch (\Exception $e) {
            return redirect()->route('items.purchase.show', ['item_id' => $item_id])
                ->with('error', '購入処理中にエラーが発生しました');
        }
    }

}