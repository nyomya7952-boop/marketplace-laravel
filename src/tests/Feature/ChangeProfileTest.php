<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class ChangeProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プロフィール変更画面で初期値が正しく設定されていること
     */
    public function test_initial_values_are_correctly_set_in_profile_edit_page()
    {
        // 1. ユーザーにログインする
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'name' => 'テストユーザー',
            'profile_image_path' => 'profile/test-image.jpg',
            'postal_code' => '123-1234',
            'address' => '東京都千代田区千代田1-1',
            'building_name' => 'テストマンション101号室',
        ]);

        // 2. プロフィール編集ページを開く
        $response = $this->actingAs($user)->get(route('user.edit'));
        $response->assertStatus(200);

        // プロフィール画像が表示されることを確認（imgタグのsrc属性）
        $response->assertSee('storage/' . $user->profile_image_path, false);

        // ユーザー名がinput要素のvalue属性に正しく設定されていることを確認
        $response->assertSee('value="' . htmlspecialchars($user->name, ENT_QUOTES) . '"', false);

        // 郵便番号がinput要素のvalue属性に正しく設定されていることを確認
        $response->assertSee('value="' . htmlspecialchars($user->postal_code, ENT_QUOTES) . '"', false);

        // 住所がinput要素のvalue属性に正しく設定されていることを確認
        $response->assertSee('value="' . htmlspecialchars($user->address, ENT_QUOTES) . '"', false);

        // 建物名がinput要素のvalue属性に正しく設定されていることを確認
        $response->assertSee('value="' . htmlspecialchars($user->building_name, ENT_QUOTES) . '"', false);
    }
}
