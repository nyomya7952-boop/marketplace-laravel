<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\Comment;

class CommentTest extends TestCase
{
    use RefreshDatabase;
    /**
     * ログイン済みのユーザーはコメントを送信できる
     *
     * @return void
     */
    public function testLoggedInUsersCanSendComments()
    {
        // 1. テストデータを作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
        ]);

        $response = $this->actingAs($seller)->post(route('items.comment', ['item_id' => $item->id]), [
            'content' => 'テストコメント',
        ]);

        // 2. ステータスコードを確認(リダイレクトされることを確認)
        $response->assertStatus(302);

        // 3. コメントが作成されたことを確認
        $this->assertDatabaseHas('comments', [
            'user_id' => $seller->id,
            'item_id' => $item->id,
            'content' => 'テストコメント',
        ]);
    }

    /**
     * ログイン前のユーザーはコメントを送信できない
     *
     * @return void
     */
    public function testUnloggedInUsersCannotSendComments()
    {
        // 1. テストデータを作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
        ]);

        // 2. 商品詳細ページにアクセス（未ログイン状態）
        $detailResponse = $this->get(route('items.detail', ['item_id' => $item->id]));
        $detailResponse->assertStatus(200);

        // 3. コメントフォームが存在することを確認
        $detailResponse->assertSee('商品へのコメント');
        $detailResponse->assertSee('コメントを入力してください');
        $detailResponse->assertSee('コメントを送信する');

        // 4. コメントを入力してコメント送信ボタンを押下する操作をシミュレート
        // （商品詳細ページからフォームを送信する形でテスト）
        $commentContent = 'テストコメント';
        $postResponse = $this->from(route('items.detail', ['item_id' => $item->id]))
            ->post(route('items.comment', ['item_id' => $item->id]), [
                'content' => $commentContent,
            ]);

        // 5. 未ログインのため、商品詳細ページにリダイレクトされることを確認
        // （サーバーサイドで認証チェックが行われ、エラーメッセージと共にリダイレクトされる）
        $postResponse->assertRedirect(route('items.detail', ['item_id' => $item->id]));
        $postResponse->assertSessionHasErrors(['content']);

        // 6. セッションエラーの内容を確認
        $errors = $postResponse->getSession()->get('errors');
        $this->assertEquals('コメントするにはログインしてください', $errors->get('content')[0]);

        // 7. リダイレクト後のページでエラーメッセージが表示されることを確認
        $redirectResponse = $this->get(route('items.detail', ['item_id' => $item->id]));
        $redirectResponse->assertSee('コメントするには');
        $redirectResponse->assertSee(route('login'));
        $redirectResponse->assertSee('ログイン');

        // 8. コメントが作成されないことを確認
        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
            'content' => $commentContent,
        ]);
    }

    /**
     * コメントが入力されていない場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function testCommentContentShouldBeRequired()
    {
        // 1. テストデータを作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
        ]);

        // 2. 商品詳細ページにアクセス（ログイン状態）
        $detailResponse = $this->actingAs($seller)->get(route('items.detail', ['item_id' => $item->id]));
        $detailResponse->assertStatus(200);

        // 3. コメントフォームが存在することを確認
        $detailResponse->assertSee('商品へのコメント');
        $detailResponse->assertSee('コメントを入力してください');
        $detailResponse->assertSee('コメントを送信する');

        // 4. コメントを入力せずにコメント送信ボタンを押下
        $postResponse = $this->actingAs($seller)
            ->from(route('items.detail', ['item_id' => $item->id]))
            ->post(route('items.comment', ['item_id' => $item->id]), [
                'content' => '',
            ]);

        // 5. バリデーションエラーのため、商品詳細ページにリダイレクトされることを確認
        $postResponse->assertRedirect(route('items.detail', ['item_id' => $item->id]));
        $postResponse->assertSessionHasErrors(['content']);

        // 6. セッションエラーの内容を確認
        $errors = $postResponse->getSession()->get('errors');
        $this->assertEquals('コメントを入力してください', $errors->get('content')[0]);

        // 7. リダイレクト後のページでエラーメッセージが表示されることを確認
        // （セッションは自動的に保持されるため、get()でアクセスすればエラーメッセージが表示される）
        $redirectResponse = $this->actingAs($seller)->get(route('items.detail', ['item_id' => $item->id]));
        $redirectResponse->assertSee('コメントを入力してください');

        // 8. コメントが作成されないことを確認
        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
        ]);
    }

    /**
     * コメントが255字以上の場合、バリデーションメッセージが表示される
     *
     * @return void
     */
    public function testCommentContentShouldBeLessThan255Characters()
    {
        // 1. テストデータを作成
        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
        ]);

        // 2. 商品詳細ページにアクセス（ログイン状態）
        $detailResponse = $this->actingAs($seller)->get(route('items.detail', ['item_id' => $item->id]));
        $detailResponse->assertStatus(200);

        // 3. コメントフォームが存在することを確認
        $detailResponse->assertSee('商品へのコメント');
        $detailResponse->assertSee('コメントを入力してください');
        $detailResponse->assertSee('コメントを送信する');

        // 4. 255文字を超えるコメント（256文字）を入力してコメント送信ボタンを押下
        $longComment = str_repeat('a', 256);
        $postResponse = $this->actingAs($seller)
            ->from(route('items.detail', ['item_id' => $item->id]))
            ->post(route('items.comment', ['item_id' => $item->id]), [
                'content' => $longComment,
            ]);

        // 5. バリデーションエラーのため、商品詳細ページにリダイレクトされることを確認
        $postResponse->assertRedirect(route('items.detail', ['item_id' => $item->id]));
        $postResponse->assertSessionHasErrors(['content']);

        // 6. セッションエラーの内容を確認
        $errors = $postResponse->getSession()->get('errors');
        $this->assertEquals('コメントは255文字以内で入力してください', $errors->get('content')[0]);

        // 7. リダイレクト後のページでエラーメッセージが表示されることを確認
        // （セッションは自動的に保持されるため、get()でアクセスすればエラーメッセージが表示される）
        $redirectResponse = $this->actingAs($seller)->get(route('items.detail', ['item_id' => $item->id]));
        $redirectResponse->assertSee('コメントは255文字以内で入力してください');

        // 8. コメントが作成されないことを確認
        $this->assertDatabaseMissing('comments', [
            'item_id' => $item->id,
        ]);
    }
}
