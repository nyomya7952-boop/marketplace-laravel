<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;

class MyListTest extends TestCase
{

    use RefreshDatabase;
    /**
     * いいねした商品だけが表示される
     *
     * @return void
     */
    public function testLikedItemsAreDisplayed()
    {
        // 1. ログインユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 出品者を作成
        $seller1 = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $seller2 = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 3. いいねする商品を作成
        $likedItem = Item::factory()->create([
            'user_id' => $seller1->id,
            'name' => 'いいねした商品',
        ]);

        // 4. いいねしていない商品を作成（これは表示されないべき）
        $notLikedItem = Item::factory()->create([
            'user_id' => $seller2->id,
            'name' => 'いいねしていない商品',
        ]);

        // 5. いいねする
        $user->likes()->create([
            'item_id' => $likedItem->id,
        ]);

        // 6. 商品一覧(マイリスト)を取得
        $response = $this->actingAs($user)->get(route('items.index', ['tab' => 'mylist']));

        // 7. ステータスコードを確認
        $response->assertStatus(200);

        // 8. いいねした商品は表示されることを確認
        $response->assertSee($likedItem->name);

        // 9. いいねしていない商品は表示されないことを確認
        $response->assertDontSee($notLikedItem->name);
    }

    /**
     * マイリストタブで購入済み商品は「Sold」と表示される
     *
     * @return void
     */
    public function testSoldItemsAreDisplayedAsSoldInMylist()
    {
        // 1. 出品者を作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. ログインユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 3. 購入済み商品を作成
        $soldItem = Item::factory()->create([
            'user_id' => $seller->id,
            'is_sold' => 'sold',
            'name' => '購入済み商品テスト',
        ]);

        // 4. 購入済み商品にいいねを登録（マイリストタブはいいねした商品のみ表示するため）
        $user->likes()->create([
            'item_id' => $soldItem->id,
        ]);

        // 5. 商品一覧(マイリスト)を取得
        $response = $this->actingAs($user)->get(route('items.index', ['tab' => 'mylist']));

        // 6. ステータスコードを確認
        $response->assertStatus(200);

        // 7. 商品名が表示されることを確認（商品が一覧に表示されていることを確認）
        $response->assertSee($soldItem->name);

        // 8. 「Sold」バッジが表示されることを確認
        $response->assertSee('Sold');
    }

    /**
     * 未認証の場合は何も表示されない
     *
     * @return void
     */
    public function testUnauthenticatedUsersGetEmptyCollection()
    {
        // 1. 商品を作成（未認証ユーザーには表示されないべき）
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'name' => 'テスト商品',
        ]);

        // 2. ログインせずにマイリストタブを表示
        $response = $this->get(route('items.index', ['tab' => 'mylist']));

        // 3. ステータスコードを確認
        $response->assertStatus(200);

        // 4. 商品が表示されないことを確認
        $response->assertDontSee($item->name);

        // 5. 未認証ユーザー向けのメッセージが表示されることを確認
        $response->assertSee('ログインすると、いいねした商品がここに表示されます');
    }
}
