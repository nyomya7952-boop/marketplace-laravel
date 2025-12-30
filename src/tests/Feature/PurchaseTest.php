<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\MasterData;
use App\Models\SoldItem;

class PurchaseTest extends TestCase
{
    use RefreshDatabase;
    /**
     * 「購入する」ボタンを押下すると購入が完了する(コンビニ支払いの場合)
     */
    public function test_successful_purchase_with_convenience_payment()
    {
        // 1. テストデータを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストマンション101号室',
        ]);

        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        $paymentMethod = MasterData::firstOrCreate([
            'type' => 'payment_method',
            'name' => 'コンビニ支払い',
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
        ]);

        // 2. 商品購入画面を開く
        $response = $this->actingAs($user)->get(route('items.purchase.show', ['item_id' => $item->id]));
        $response->assertStatus(200);

        // 3. StripeServiceをモックして、購入処理を正常に完了させる
        $checkoutSession = new \stdClass();
        $checkoutSession->id = 'test_session_id';
        $checkoutSession->url = 'https://checkout.stripe.com/test';

        $this->mock(\App\Services\StripeService::class, function ($mock) use ($checkoutSession) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn($checkoutSession);
        });

        // 4. 「購入する」ボタンを押下する
        $response = $this->actingAs($user)->post(route('items.purchase', ['item_id' => $item->id]), [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 5. Stripeのチェックアウトページにリダイレクトされることを確認
        $response->assertRedirect($checkoutSession->url);

        // 6. 購入が完了したことを確認（コンビニ支払いの場合は入金待ち状態）
        $this->assertDatabaseHas('sold_items', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 7. 商品の状態が入金待ち（pending）に更新されることを確認
        $item->refresh();
        $this->assertEquals('pending', $item->is_sold);

        // 8. コンビニ支払いが完了したことを想定したWebhook処理（pending→sold）
        // StripeのWebhookで"checkout.session.async_payment_succeeded"イベントを受け取ったことを想定
        $stripeSession = new \stdClass();
        $stripeSession->id = 'test_session_id';

        // Webhookコントローラーを直接呼び出して、コンビニ支払いの入金完了をシミュレート
        $webhookController = new \App\Http\Controllers\StripeWebhookController(
            app(\App\Services\StripeService::class)
        );

        // handleAsyncPaymentSucceededメソッドをリフレクションで呼び出す
        $reflection = new \ReflectionClass($webhookController);
        $method = $reflection->getMethod('handleAsyncPaymentSucceeded');
        $method->setAccessible(true);
        $method->invoke($webhookController, $stripeSession);

        // 9. 商品の状態が決済完了（sold）に更新されることを確認
        $item->refresh();
        $this->assertEquals('sold', $item->is_sold);

        // 10. sold_itemsテーブルに重複せず1レコードのみ存在することを確認
        $this->assertEquals(1, \App\Models\SoldItem::where('item_id', $item->id)
            ->where('user_id', $user->id)
            ->where('payment_method_id', $paymentMethod->id)
            ->count());
    }

    /**
     * 「購入する」ボタンを押下すると購入が完了する(カード支払いの場合)
     */
    public function test_successful_purchase_with_card_payment()
    {
        // 1. テストデータを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストマンション101号室',
        ]);

        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        $paymentMethod = MasterData::firstOrCreate([
            'type' => 'payment_method',
            'name' => 'カード支払い',
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
        ]);

        // 2. 商品購入画面を開く
        $response = $this->actingAs($user)->get(route('items.purchase.show', ['item_id' => $item->id]));
        $response->assertStatus(200);

        // 3. StripeServiceをモックして、購入処理を正常に完了させる
        $checkoutSession = new \stdClass();
        $checkoutSession->id = 'test_session_id';
        $checkoutSession->url = 'https://checkout.stripe.com/test';

        $stripeSession = new \stdClass();
        $stripeSession->payment_status = 'paid';

        $this->mock(\App\Services\StripeService::class, function ($mock) use ($checkoutSession, $stripeSession) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn($checkoutSession);
            $mock->shouldReceive('retrieveCheckoutSession')
                ->with($checkoutSession->id)
                ->once()
                ->andReturn($stripeSession);
        });

        // 4. 「購入する」ボタンを押下する
        $response = $this->actingAs($user)->post(route('items.purchase', ['item_id' => $item->id]), [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 5. Stripeのチェックアウトページにリダイレクトされることを確認
        $response->assertRedirect($checkoutSession->url);

        // 6. セッションに購入情報が保存されていることを確認
        $response->assertSessionHas('pending_purchase');
        $pendingPurchase = $response->getSession()->get('pending_purchase');
        $this->assertEquals($item->id, $pendingPurchase['item_id']);
        $this->assertEquals($paymentMethod->id, $pendingPurchase['payment_method_id']);

        // 7. この時点ではまだ購入が完了していないことを確認（カード支払いの場合）
        $this->assertDatabaseMissing('sold_items', [
            'item_id' => $item->id,
            'user_id' => $user->id,
        ]);
        $item->refresh();
        $this->assertNull($item->is_sold);

        // 8. 購入完了画面にアクセス（Stripeで決済完了後）
        $successResponse = $this->actingAs($user)->get(route('items.purchase.success', [
            'item_id' => $item->id,
            'session_id' => $checkoutSession->id,
        ]));
        $successResponse->assertStatus(200);
        $successResponse->assertViewIs('item.purchase-success');

        // 9. 購入が完了したことを確認（カード支払いの場合は決済完了状態）
        $this->assertDatabaseHas('sold_items', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
        ]);
    }

    /**
     * 購入した商品は商品一覧画面にて「sold」と表示される
     */
    public function test_successful_purchase_is_displayed_as_sold_in_item_list()
    {
        // 1. テストデータを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストマンション101号室',
        ]);

        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        $paymentMethod = MasterData::firstOrCreate([
            'type' => 'payment_method',
            'name' => 'カード支払い',
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
        ]);

        // 2. 商品購入画面を開く
        $response = $this->actingAs($user)->get(route('items.purchase.show', ['item_id' => $item->id]));
        $response->assertStatus(200);

        // 3. StripeServiceをモックして、購入処理を正常に完了させる
        $checkoutSession = new \stdClass();
        $checkoutSession->id = 'test_session_id';
        $checkoutSession->url = 'https://checkout.stripe.com/test';

        $stripeSession = new \stdClass();
        $stripeSession->payment_status = 'paid';

        $this->mock(\App\Services\StripeService::class, function ($mock) use ($checkoutSession, $stripeSession) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn($checkoutSession);
            $mock->shouldReceive('retrieveCheckoutSession')
                ->with($checkoutSession->id)
                ->once()
                ->andReturn($stripeSession);
        });

        // 4. 「購入する」ボタンを押下する
        $response = $this->actingAs($user)->post(route('items.purchase', ['item_id' => $item->id]), [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 5. Stripeのチェックアウトページにリダイレクトされることを確認
        $response->assertRedirect($checkoutSession->url);

        // 6. セッションに購入情報が保存されていることを確認
        $response->assertSessionHas('pending_purchase');
        $pendingPurchase = $response->getSession()->get('pending_purchase');
        $this->assertEquals($item->id, $pendingPurchase['item_id']);
        $this->assertEquals($paymentMethod->id, $pendingPurchase['payment_method_id']);

        // 7. この時点ではまだ購入が完了していないことを確認（カード支払いの場合）
        $this->assertDatabaseMissing('sold_items', [
            'item_id' => $item->id,
            'user_id' => $user->id,
        ]);
        $item->refresh();
        $this->assertNull($item->is_sold);

        // 8. 購入完了画面にアクセス（Stripeで決済完了後）
        $successResponse = $this->actingAs($user)->get(route('items.purchase.success', [
            'item_id' => $item->id,
            'session_id' => $checkoutSession->id,
        ]));
        $successResponse->assertStatus(200);
        $successResponse->assertViewIs('item.purchase-success');

        // 9. 購入が完了したことを確認（カード支払いの場合は決済完了状態）
        $this->assertDatabaseHas('sold_items', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 10. 商品の状態が決済完了（sold）に更新されることを確認
        $item->refresh();
        $this->assertEquals('sold', $item->is_sold);

        // 11. 商品一覧画面を開く
        $response = $this->actingAs($user)->get(route('items.index'));
        $response->assertStatus(200);

        // 12. 商品一覧画面にて「sold」と表示されることを確認
        $response->assertSee('sold');
    }

    /**
     * 「プロフィール/購入した商品一覧」に追加されている
     */
    public function test_successful_purchase_is_displayed_in_my_purchase_list()
    {
        // 1. テストデータを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストマンション101号室',
        ]);

        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        $paymentMethod = MasterData::firstOrCreate([
            'type' => 'payment_method',
            'name' => 'カード支払い',
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
        ]);

        // 2. 商品購入画面を開く
        $response = $this->actingAs($user)->get(route('items.purchase.show', ['item_id' => $item->id]));
        $response->assertStatus(200);

        // 3. StripeServiceをモックして、購入処理を正常に完了させる
        $checkoutSession = new \stdClass();
        $checkoutSession->id = 'test_session_id';
        $checkoutSession->url = 'https://checkout.stripe.com/test';

        $stripeSession = new \stdClass();
        $stripeSession->payment_status = 'paid';

        $this->mock(\App\Services\StripeService::class, function ($mock) use ($checkoutSession, $stripeSession) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn($checkoutSession);
            $mock->shouldReceive('retrieveCheckoutSession')
                ->with($checkoutSession->id)
                ->once()
                ->andReturn($stripeSession);
        });

        // 4. 「購入する」ボタンを押下する
        $response = $this->actingAs($user)->post(route('items.purchase', ['item_id' => $item->id]), [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 5. Stripeのチェックアウトページにリダイレクトされることを確認
        $response->assertRedirect($checkoutSession->url);

        // 6. セッションに購入情報が保存されていることを確認
        $response->assertSessionHas('pending_purchase');
        $pendingPurchase = $response->getSession()->get('pending_purchase');
        $this->assertEquals($item->id, $pendingPurchase['item_id']);
        $this->assertEquals($paymentMethod->id, $pendingPurchase['payment_method_id']);

        // 7. この時点ではまだ購入が完了していないことを確認（カード支払いの場合）
        $this->assertDatabaseMissing('sold_items', [
            'item_id' => $item->id,
            'user_id' => $user->id,
        ]);
        $item->refresh();
        $this->assertNull($item->is_sold);

        // 8. 購入完了画面にアクセス（Stripeで決済完了後）
        $successResponse = $this->actingAs($user)->get(route('items.purchase.success', [
            'item_id' => $item->id,
            'session_id' => $checkoutSession->id,
        ]));
        $successResponse->assertStatus(200);
        $successResponse->assertViewIs('item.purchase-success');

        // 9. 購入が完了したことを確認（カード支払いの場合は決済完了状態）
        $this->assertDatabaseHas('sold_items', [
            'item_id' => $item->id,
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 10. 商品の状態が決済完了（sold）に更新されることを確認
        $item->refresh();
        $this->assertEquals('sold', $item->is_sold);

        // 11. 「プロフィール/購入した商品一覧」に追加されていることを確認
        $response = $this->actingAs($user)->get(route('user.profile', ['page' => 'buy']));
        $response->assertStatus(200);
        $response->assertSee($item->name);
    }
}
