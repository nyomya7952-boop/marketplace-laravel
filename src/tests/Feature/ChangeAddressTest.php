<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\MasterData;
use App\Models\SoldItem;
use Illuminate\Support\Facades\Hash;

class ChangeAddressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 送付先住所変更画面にて登録した住所が商品購入画面に反映されている
     */
    public function testShippingAddressReflectedInPurchasePage()
    {
        // 1. ユーザーにログインする
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'postal_code' => '000',
            'address' => '住所未設定',
        ]);

        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
        ]);

        // 2. 送付先住所変更画面で住所を登録する
        $newPostalCode = '123-4567';
        $newAddress = '東京都渋谷区テスト1-2-3';
        $newBuildingName = 'テストマンション101号室';

        $response = $this->actingAs($user)->post(route('shipping.update', ['item_id' => $item->id]), [
            'postal_code' => $newPostalCode,
            'address' => $newAddress,
            'building_name' => $newBuildingName,
        ]);

        $response->assertRedirect(route('items.purchase.show', ['item_id' => $item->id]));

        // 3. 商品購入画面を再度開く
        $response = $this->actingAs($user)->get(route('items.purchase.show', ['item_id' => $item->id]));

        // 登録した住所が商品購入画面に正しく反映される
        $response->assertStatus(200);
        $response->assertSee($newPostalCode);
        $response->assertSee($newAddress);
        $response->assertSee($newBuildingName);
    }

    /**
     * 購入した商品に送付先住所が紐づいて登録される
     */
    public function testShippingAddressSavedWithPurchase()
    {
        // 1. ユーザーにログインする
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'postal_code' => '000',
            'address' => '住所未設定',
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

        // 2. 送付先住所変更画面で住所を登録する
        $newPostalCode = '123-4567';
        $newAddress = '東京都渋谷区テスト1-2-3';
        $newBuildingName = 'テストマンション101号室';

        $response = $this->actingAs($user)->post(route('shipping.update', ['item_id' => $item->id]), [
            'postal_code' => $newPostalCode,
            'address' => $newAddress,
            'building_name' => $newBuildingName,
        ]);

        $response->assertRedirect(route('items.purchase.show', ['item_id' => $item->id]));

        // 3. 商品を購入する（コンビニ支払いの場合、pendingPurchaseが呼ばれる）
        // StripeServiceをモックして、購入処理を正常に完了させる
        $checkoutSession = new \stdClass();
        $checkoutSession->id = 'test_session_id';
        $checkoutSession->url = 'https://checkout.stripe.com/test';

        $this->mock(\App\Services\StripeService::class, function ($mock) use ($checkoutSession) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn($checkoutSession);
        });

        $response = $this->actingAs($user)->post(route('items.purchase', ['item_id' => $item->id]), [
            'payment_method_id' => $paymentMethod->id,
        ]);

        // 正しく送付先住所が紐づいている
        $soldItem = SoldItem::where('item_id', $item->id)
            ->where('user_id', $user->id)
            ->first();

        $this->assertNotNull($soldItem);
        $this->assertEquals($newPostalCode, $soldItem->shipping_postal_code);
        $this->assertEquals($newAddress, $soldItem->shipping_address);
        $this->assertEquals($newBuildingName, $soldItem->shipping_building_name);
    }
}
