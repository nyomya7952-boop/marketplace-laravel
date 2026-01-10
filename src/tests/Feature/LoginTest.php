<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function testEmailValidationWhenEmpty()
    {
        // 1. ログインページを開く
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // 2. メールアドレスを入力せずに他の必要項目を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('login'), [
            'email' => '',
            'password' => 'password123',
        ]);

        // バリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->get('email')[0]);
    }

    /**
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function testPasswordValidationWhenEmpty()
    {
        // 1. ログインページを開く
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // 2. パスワードを入力せずに他の必要項目を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => '',
        ]);

        // バリデーションメッセージが表示される
        $response->assertSessionHasErrors(['password']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('パスワードを入力してください', $errors->get('password')[0]);
    }

    /**
     * 入力情報が間違っている場合、バリデーションメッセージが表示される
     */
    public function testLoginFailsWithInvalidCredentials()
    {
        // 1. ログインページを開く
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // 2. 必要項目を登録されていない情報を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('login'), [
            'email' => 'nonexistent@example.com',
            'password' => 'wrongpassword',
        ]);

        // バリデーションメッセージが表示される
        $response->assertSessionHasErrors(['email']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('ログイン情報が登録されていません', $errors->get('email')[0]);
    }

    /**
     * 正しい情報が入力された場合、ログイン処理が実行される
     */
    public function testSuccessfulLoginWithValidCredentials()
    {
        // メール認証済みのユーザーを作成
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        // 1. ログインページを開く
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        // 2. 全ての必要項目を入力する
        // 3. ログインボタンを押す
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        // ログイン処理が実行される
        $response->assertRedirect(route('items.index'));
        $this->assertAuthenticatedAs($user);
    }
}
