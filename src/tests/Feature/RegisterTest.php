<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 名前が入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_name_validation_when_empty()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        $response = $this->post(route('register.post'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['name']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('お名前を入力してください', $errors->get('name')[0]);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    /**
     * メールアドレスが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_email_validation_when_empty()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        $response = $this->post(route('register.post'), [
            'name' => 'テストユーザー',
            'email' => '',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('メールアドレスを入力してください', $errors->get('email')[0]);
        $this->assertDatabaseMissing('users', ['name' => 'テストユーザー']);
    }

    /**
     * パスワードが入力されていない場合、バリデーションメッセージが表示される
     */
    public function test_password_validation_when_empty()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        $response = $this->post(route('register.post'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('パスワードを入力してください', $errors->get('password')[0]);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    /**
     * パスワードが7文字以下の場合、バリデーションメッセージが表示される
     */
    public function test_password_validation_when_less_than_8_characters()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        $response = $this->post(route('register.post'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'pass123',
            'password_confirmation' => 'pass123',
        ]);

        $response->assertSessionHasErrors(['password']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('パスワードは8文字以上で入力してください', $errors->get('password')[0]);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    /**
     * パスワードが確認用パスワードと一致しない場合、バリデーションメッセージが表示される
     */
    public function test_password_confirmation_validation_when_mismatch()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        $response = $this->post(route('register.post'), [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password456',
        ]);

        $response->assertSessionHasErrors(['password_confirmation']);
        $errors = $response->getSession()->get('errors');
        $this->assertEquals('確認用パスワードと一致しません', $errors->get('password_confirmation')[0]);
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    /**
     * 全ての項目が入力されている場合、会員情報が登録され、メール認証画面に遷移される
     */
    public function test_successful_registration_redirects_to_verification_notice()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);

        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.post'), $userData);

        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }
}
