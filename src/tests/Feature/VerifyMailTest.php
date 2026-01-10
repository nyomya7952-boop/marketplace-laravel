<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Tests\TestCase;
use App\Models\User;

class VerifyMailTest extends TestCase
{
    use RefreshDatabase;
    /**
     * 会員登録後、認証メールが送信される
     */
    public function testSuccessfulRegistrationSendsVerificationEmail()
    {
        // 通知をモック
        Notification::fake();

        // 会員登録する
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.post'), $userData);
        $response->assertRedirect(route('verification.notice'));

        // ユーザーがデータベースに登録されていることを確認
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);

        // 登録されたユーザーを取得
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        // 認証メールが送信されたことを確認
        Notification::assertSentTo(
            $user,
            VerifyEmail::class
        );
    }

    /**
     * メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
     */
    public function testVerificationNoticeHasLinkToMailSite()
    {
        // 1. 会員登録する
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.post'), $userData);
        $response->assertRedirect(route('verification.notice'));

        // 2. メール認証誘導画面を表示する
        $response = $this->get(route('verification.notice'));
        $response->assertStatus(200);

        // 3. 「認証はこちらから」ボタンが存在し、メール認証サイト（http://localhost:8025）へのリンクがあることを確認
        // ボタンのリンク先URLが正しいことを確認
        $response->assertSee('href="http://localhost:8025"', false);

        // ボタンの文言が正しいことを確認
        $response->assertSee('認証はこちらから');
    }

    /**
     * メール認証サイトのメール認証を完了すると、プロフィール設定画面に遷移する
     */
    public function testEmailVerificationRedirectsToProfileSetting()
    {
        // 1. 会員登録する
        $userData = [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.post'), $userData);
        $response->assertRedirect(route('verification.notice'));

        // 2. ユーザーを取得し、認証前の状態を確認
        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at, '認証前はemail_verified_atがnullであること');
        // 認証前はログインしていないこと
        $this->assertGuest();

        // 3. メール認証サイトのメール認証を完了する
        $verificationUrl = \Illuminate\Support\Facades\URL::signedRoute(
            'verification.verify',
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );
        $response = $this->get($verificationUrl);
        $response->assertRedirect(route('user.edit'));

        // 4. 認証後の状態を確認
        $user->refresh();
        $this->assertNotNull($user->email_verified_at, '認証後はemail_verified_atが設定されていること');
        // 認証後はログイン状態であること
        $this->assertAuthenticatedAs($user);

        // 5. プロフィール設定画面に遷移できることを確認
        $response = $this->get(route('user.edit'));
        $response->assertStatus(200);
    }
}
