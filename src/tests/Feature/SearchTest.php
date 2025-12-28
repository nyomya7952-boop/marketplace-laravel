<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\MasterData;
use Illuminate\Support\Facades\Hash;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 「商品名」で部分一致検索ができる
     */
    public function test_search_by_item_name_partial_match()
    {
        // テスト用のユーザーとマスタデータを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        // 検索キーワードを含む商品を作成
        $matchingItem1 = Item::factory()->create([
            'name' => 'テスト商品A',
            'user_id' => $user->id,
            'price' => 1000,
            'description' => 'テスト用商品A',
            'condition_id' => $condition->id,
            'is_sold' => false,
        ]);

        $matchingItem2 = Item::factory()->create([
            'name' => '商品テストB',
            'user_id' => $user->id,
            'price' => 2000,
            'description' => 'テスト用商品B',
            'condition_id' => $condition->id,
            'is_sold' => false,
        ]);

        // 検索キーワードを含まない商品を作成
        $nonMatchingItem = Item::factory()->create([
            'name' => 'サンプル商品',
            'user_id' => $user->id,
            'price' => 3000,
            'description' => 'サンプル用商品',
            'condition_id' => $condition->id,
            'is_sold' => false,
        ]);

        // 1. 検索欄にキーワードを入力
        // 2. 検索ボタンを押す
        $response = $this->get(route('items.index', ['search' => 'テスト']));

        // 部分一致する商品が表示される
        $response->assertStatus(200);
        $response->assertSee($matchingItem1->name);
        $response->assertSee($matchingItem2->name);
        $response->assertDontSee($nonMatchingItem->name);
    }

    /**
     * 検索状態がマイリストでも保持されている
     */
    public function test_search_state_preserved_in_mylist()
    {
        // テスト用のユーザーとマスタデータを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        // 商品を作成
        $item = Item::factory()->create([
            'name' => 'テスト商品',
            'user_id' => $user->id,
            'price' => 1000,
            'description' => 'テスト用商品',
            'condition_id' => $condition->id,
            'is_sold' => false,
        ]);

        // ユーザーがいいねする
        $user->likes()->create([
            'item_id' => $item->id,
        ]);

        // 1. ホームページで商品を検索
        // 2. 検索結果が表示される
        $searchKeyword = 'テスト';
        $response = $this->actingAs($user)->get(route('items.index', ['search' => $searchKeyword]));
        $response->assertStatus(200);
        $response->assertSee($searchKeyword);

        // 3. マイリストページに遷移
        $response = $this->actingAs($user)->get(route('items.index', ['tab' => 'mylist', 'search' => $searchKeyword]));

        // 検索キーワードが保持されている
        $response->assertStatus(200);
        $response->assertSee($searchKeyword);
        // 検索パラメータがビューに渡されていることを確認
        $this->assertEquals($searchKeyword, $response->viewData('search'));
    }
}
