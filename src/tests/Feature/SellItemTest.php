<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\MasterData;
use App\Models\Category;
use App\Models\Brand;

class SellItemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 商品出品画面にて必要な情報が保存できること（カテゴリ、商品の状態、商品名、ブランド名、商品の説明、販売価格）
     */
    public function test_required_information_can_be_saved_in_sell_item_page()
    {
        // 1. ユーザーにログインする
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category1 = Category::factory()->create([
            'name' => 'テストカテゴリ1',
        ]);
        $category2 = Category::factory()->create([
            'name' => 'テストカテゴリ2',
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        // 2. 商品出品画面を開く
        $response = $this->actingAs($user)->get(route('items.create.show'));
        $response->assertStatus(200);

        // 3. 必要な情報を入力する（画像ファイルも含む）
        $image = \Illuminate\Http\UploadedFile::fake()->image('test-item.jpg', 100, 100);

        $response = $this->actingAs($user)->post(route('items.create'), [
            'category_ids' => [$category1->id, $category2->id], // 配列で送る
            'condition_id' => $condition->id,
            'name' => 'テスト商品',
            'brand_name' => 'テストブランド',
            'description' => 'テスト商品の説明',
            'price' => 1000,
            'image' => $image,
        ]);

        // 4. 商品出品画面にて必要な情報が保存できていることを確認
        $response->assertRedirect(route('items.index'));
        $response->assertSessionHas('success', '商品を出品しました');

        // itemsテーブルに商品情報が保存されていることを確認
        $this->assertDatabaseHas('items', [
            'user_id' => $user->id,
            'name' => 'テスト商品',
            'description' => 'テスト商品の説明',
            'price' => 1000,
            'condition_id' => $condition->id,
        ]);

        // 商品のbrand_idが正しく設定されていることを確認（ブランド名から作成される）
        $item = Item::where('name', 'テスト商品')->first();
        $this->assertNotNull($item);
        $this->assertNotNull($item->brand_id);
        $this->assertDatabaseHas('brands', [
            'id' => $item->brand_id,
            'name' => 'テストブランド',
        ]);

        // item_category中間テーブルにカテゴリが関連付けられていることを確認
        $this->assertDatabaseHas('item_category', [
            'item_id' => $item->id,
            'category_id' => $category1->id,
            'category_id' => $category2->id,
        ]);

        // master_dataテーブルに条件が保存されていることを確認（conditionsテーブルではない）
        $this->assertDatabaseHas('master_data', [
            'id' => $condition->id,
            'type' => 'condition',
            'name' => '良好',
        ]);
    }
}
