<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\MasterData;
use App\Models\SoldItem;

class GetProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 必要な情報が取得できる（プロフィール画像、ユーザー名、出品した商品一覧、購入した商品一覧）
     */
    public function testProfilePageDisplaysAllRequiredInformation()
    {
        // 1. ユーザーにログインする
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テストユーザー',
            'profile_image_path' => 'profile/test-image.jpg',
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

        // 出品した商品を作成
        $sellItem1 = Item::factory()->create([
            'user_id' => $user->id,
            'condition_id' => $condition->id,
            'name' => '出品商品1',
        ]);

        $sellItem2 = Item::factory()->create([
            'user_id' => $user->id,
            'condition_id' => $condition->id,
            'name' => '出品商品2',
        ]);

        // 購入した商品を作成（別のユーザーが出品した商品を購入）
        $purchasedItem1 = Item::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
            'name' => '購入商品1',
        ]);

        $purchasedItem2 = Item::factory()->create([
            'user_id' => $seller->id,
            'condition_id' => $condition->id,
            'name' => '購入商品2',
        ]);

        // SoldItemを作成（購入履歴）
        SoldItem::create([
            'item_id' => $purchasedItem1->id,
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'shipping_postal_code' => '123-4567',
            'shipping_address' => '東京都渋谷区テスト1-2-3',
            'shipping_building_name' => 'テストマンション101号室',
        ]);

        SoldItem::create([
            'item_id' => $purchasedItem2->id,
            'user_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'shipping_postal_code' => '123-4567',
            'shipping_address' => '東京都渋谷区テスト1-2-3',
            'shipping_building_name' => 'テストマンション101号室',
        ]);

        // 2. プロフィールページを開く（デフォルトは出品した商品タブ）
        $response = $this->actingAs($user)->get(route('user.profile'));

        // プロフィール画像が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee('storage/' . $user->profile_image_path);

        // ユーザー名が表示されることを確認
        $response->assertSee($user->name);

        // 出品した商品一覧が表示されることを確認（デフォルトタブ）
        $response->assertSee($sellItem1->name);
        $response->assertSee($sellItem2->name);

        // 購入した商品一覧タブに切り替え
        $response = $this->actingAs($user)->get(route('user.profile', ['page' => 'buy']));

        // 購入した商品一覧が表示されることを確認
        $response->assertStatus(200);
        $response->assertSee($purchasedItem1->name);
        $response->assertSee($purchasedItem2->name);
    }
}
