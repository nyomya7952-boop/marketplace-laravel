<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Item;
use App\Models\User;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MasterData;
use App\Models\Comment;
use App\Models\Like;

class ItemDetailTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 必要な情報が表示される
     * （商品画像、商品名、ブランド名、価格、いいね数、コメント数、
     * 商品説明、商品情報（カテゴリ、商品の状態）、
     * コメントしたユーザー情報、コメント内容）
     *
     * @return void
     */
    public function test_required_information_is_displayed()
    {
        // 1. テストデータを作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $brand = Brand::factory()->create([
            'name' => 'テストブランド',
        ]);

        $category1 = Category::factory()->create([
            'name' => 'カテゴリ1',
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        $commentUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 商品を作成
        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'brand_id' => $brand->id,
            'condition_id' => $condition->id,
            'name' => 'テスト商品',
            'price' => 10000,
            'description' => 'テスト商品の説明',
        ]);

        // 3. カテゴリを関連付け
        $item->categories()->attach($category1->id);

        // 4. いいねを作成
        $likeUser = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $item->likes()->create([
            'user_id' => $likeUser->id,
        ]);

        // 5. コメントを作成
        $comment = Comment::factory()->create([
            'item_id' => $item->id,
            'user_id' => $commentUser->id,
            'content' => 'テストコメント',
        ]);

        // 6. 商品詳細ページを取得
        $response = $this->get(route('items.detail', ['item_id' => $item->id]));

        // 7. ステータスコードを確認
        $response->assertStatus(200);

        // 8. 商品名が表示されることを確認
        $response->assertSee($item->name);

        // 9. ブランド名が表示されることを確認
        $response->assertSee($brand->name);

        // 10. 価格が表示されることを確認
        $response->assertSee('¥' . number_format($item->price));

        // 11. いいね数が表示されることを確認
        $response->assertSee($item->likes->count());

        // 12. コメント数が表示されることを確認
        $response->assertSee($item->comments->count());

        // 13. 商品説明が表示されることを確認
        $response->assertSee($item->description);

        // 14. カテゴリが表示されることを確認
        $response->assertSee($category1->name);

        // 15. 商品の状態が表示されることを確認
        $response->assertSee($condition->name);

        // 16. コメントしたユーザー情報が表示されることを確認
        $response->assertSee($commentUser->name);

        // 17. コメント内容が表示されることを確認
        $response->assertSee($comment->content);
    }

    /**
     * 複数選択されたカテゴリが表示されているか
     *
     * @return void
     */
    public function test_multiple_selected_categories_are_displayed()
    {
        // 1. テストデータを作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $category1 = Category::factory()->create([
            'name' => 'カテゴリ1',
        ]);
        $category2 = Category::factory()->create([
            'name' => 'カテゴリ2',
        ]);

        // 2. 商品を作成
        $item = Item::factory()->create([
            'user_id' => $seller->id,
            'name' => 'テスト商品',
        ]);

        // 3. カテゴリを関連付け
        $item->categories()->attach([$category1->id, $category2->id]);

        // 4. 商品詳細ページを取得
        $response = $this->get(route('items.detail', ['item_id' => $item->id]));

        // 5. ステータスコードを確認
        $response->assertStatus(200);

        // 6. カテゴリが表示されることを確認（複数のカテゴリ）
        $response->assertSee($category1->name);
        $response->assertSee($category2->name);
    }
}
