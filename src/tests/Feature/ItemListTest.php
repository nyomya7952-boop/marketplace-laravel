<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Item;
use App\Models\User;

class ItemListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 全商品を取得する（ログインしていない状態）
     *
     * @return void
     */
    public function test_get_all_items()
    {
        // 1. 複数の出品者を作成
        $seller1 = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $seller2 = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $seller3 = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 複数の商品を作成
        $item1 = Item::factory()->create([
            'user_id' => $seller1->id,
            'name' => '商品1',
        ]);
        $item2 = Item::factory()->create([
            'user_id' => $seller2->id,
            'name' => '商品2',
        ]);
        $item3 = Item::factory()->create([
            'user_id' => $seller3->id,
            'name' => '商品3',
        ]);

        // 3. 商品一覧を取得（ログインしていない状態）
        $response = $this->get(route('items.index'));

        // 4. ステータスコードを確認
        $response->assertStatus(200);

        // 5. すべての商品が表示されることを確認
        $response->assertSee($item1->name);
        $response->assertSee($item2->name);
        $response->assertSee($item3->name);
    }

    /**
     * 購入済み商品は「Sold」と表示される
     *
     * @return void
     */
    public function test_sold_items_are_displayed_as_sold()
    {
        // 1. 出品者を作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 購入済み商品を作成
        $soldItem = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => 'sold',
            'name' => '購入済み商品テスト',
        ]);

        // 3. 商品一覧を取得（ログインしていない状態）
        $response = $this->get(route('items.index'));

        // 4. ステータスコードを確認
        $response->assertStatus(200);

        // 5. 商品名が表示されることを確認（商品が一覧に表示されていることを確認）
        $response->assertSee($soldItem->name);

        // 6. 「Sold」バッジが表示されることを確認
        $response->assertSee('Sold');
    }

    /**
     * 自分が出品した商品は表示されない
     *
     * @return void
     */
    public function test_items_sold_by_the_user_are_not_displayed()
    {
        // 1. ログインユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 自分が出品した商品を作成
        $myItem = Item::factory()->create([
            'user_id' => $user->id,
            'name' => '自分の商品',
        ]);

        // 3. 他のユーザーが出品した商品を作成（これは表示されるべき）
        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $otherItem = Item::factory()->create([
            'user_id' => $otherUser->id,
            'name' => '他の人の商品',
        ]);

        // 4. ログイン状態で商品一覧を取得
        $response = $this->actingAs($user)->get(route('items.index'));

        // 5. ステータスコードを確認
        $response->assertStatus(200);

        // 6. 自分が出品した商品は表示されないことを確認
        $response->assertDontSee($myItem->name);

        // 7. 他のユーザーが出品した商品は表示されることを確認
        $response->assertSee($otherItem->name);
    }
}
