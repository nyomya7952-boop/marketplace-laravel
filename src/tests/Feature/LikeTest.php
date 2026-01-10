<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;

class LikeTest extends TestCase
{
    use RefreshDatabase;
    /**
     * いいねアイコンを押下することによって、いいねした商品として登録することができる。
     *
     */
    public function testLikedItemsAreDisplayed()
    {
        // 1. ログインユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 出品者を作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 3. いいねする商品を作成
        $likedItem = Item::factory()->create([
            'user_id' => $seller->id,
            'name' => 'いいねした商品',
        ]);

        // 4. いいねする前のいいね数を取得
        $initialLikeCount = $likedItem->likes->count();

        // 5. いいねする
        $user->likes()->create([
            'item_id' => $likedItem->id,
        ]);

        // 6. いいねした商品がDB登録されていることを確認
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);

        // 7. いいね後の商品詳細ページを取得
        $likedItem->refresh();
        $response = $this->actingAs($user)->get(route('items.detail', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);

        // 8. いいね数が1増加していることを確認
        $newLikeCount = $likedItem->likes->count();
        $this->assertEquals($initialLikeCount + 1, $newLikeCount, 'いいね数が1増加していること');
        // いいね後のいいね数が表示されていることを確認
        $response->assertSee((string)$newLikeCount);
    }

    /**
     * いいねアイコンを押下したらアイコンの色が変化することを確認
     *
     */
    public function testLikeButtonChangesColorWhenClicked()
    {
        // 1. ログインユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 出品者を作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 3. いいねする商品を作成
        $likedItem = Item::factory()->create([
            'user_id' => $seller->id,
            'name' => 'いいねした商品',
        ]);

        // 4. いいね前の商品詳細ページを取得し、色が変化していないことを確認
        $response = $this->actingAs($user)->get(route('items.detail', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        // いいね前は色が変化していないこと（クラス属性に含まれていないことを確認）
        $response->assertDontSee('class="detail__like-button detail__like-button--liked"', false);

        // 5. いいねアイコンを押下する
        $response = $this->actingAs($user)->post(route('items.like.toggle', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'isLiked' => true]);

        // 6. いいね後の商品詳細ページを取得し、色が変化していることを確認
        $response = $this->actingAs($user)->get(route('items.detail', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        // いいね後は色が変化していること（クラス属性に含まれていることを確認）
        $response->assertSee('class="detail__like-button detail__like-button--liked"', false);
    }

    /**
     * 再度いいねアイコンを押下することによって、いいねを解除することができることを確認
     *
     */
    public function testLikeButtonChangesColorWhenClickedAgain()
    {
       // 1. ログインユーザーを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 2. 出品者を作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 3. いいねする商品を作成
        $likedItem = Item::factory()->create([
            'user_id' => $seller->id,
            'name' => 'いいねした商品',
        ]);

        // 4. いいね前の商品詳細ページを取得し、色が変化していないことを確認
        $response = $this->actingAs($user)->get(route('items.detail', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        // いいね前は色が変化していないこと（クラス属性に含まれていないことを確認）
        $response->assertDontSee('class="detail__like-button detail__like-button--liked"', false);

        // 5. いいねする前のいいね数を取得
        $initialLikeCount = $likedItem->likes->count();

        // 6. いいねアイコンを押下する
        $response = $this->actingAs($user)->post(route('items.like.toggle', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'isLiked' => true]);

        // 7. いいね後の商品詳細ページを取得し、色が変化していることを確認
        $response = $this->actingAs($user)->get(route('items.detail', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        // いいね後は色が変化していること（クラス属性に含まれていることを確認）
        $response->assertSee('class="detail__like-button detail__like-button--liked"', false);

        // 8. 再度いいねアイコンを押下する（いいね解除）
        $response = $this->actingAs($user)->post(route('items.like.toggle', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'isLiked' => false]);

        // 9. いいね解除後の商品詳細ページを取得し、色がもとに戻っていることを確認
        $likedItem->refresh();
        $response = $this->actingAs($user)->get(route('items.detail', ['item_id' => $likedItem->id]));
        $response->assertStatus(200);
        // 色がもとに戻っていること（クラス属性に含まれていないことを確認）
        $response->assertDontSee('class="detail__like-button detail__like-button--liked"', false);

        // 10. いいね数がもとに戻っていることを確認
        $newLikeCount = $likedItem->likes->count();
        $this->assertEquals($initialLikeCount, $newLikeCount, 'いいね数がもとに戻っていること');
        // いいね解除後のいいね数が表示されていることを確認
        $response->assertSee((string)$newLikeCount);

        // 11. いいねがDBから削除されていることを確認
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'item_id' => $likedItem->id,
        ]);
    }
}
