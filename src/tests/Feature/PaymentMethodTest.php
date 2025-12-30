<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Item;
use App\Models\MasterData;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    /**
     * プルダウンで選択した支払い方法が小計画面の支払い方法欄に表示される
     *
     * @return void
     */
    public function test_selected_payment_method_is_displayed_in_summary_page()
    {
        // 1. テストデータを作成
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'postal_code' => '123-4567',
            'address' => '東京都渋谷区テスト1-2-3',
            'building_name' => 'テストマンション101号室',
        ]);

        $seller = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $paymentMethod = MasterData::firstOrCreate([
            'type' => 'payment_method',
            'name' => 'カード支払い',
        ]);

        $item = Item::factory()->create([
            'user_id' => $seller->id,
        ]);

        // 2. 商品購入画面を開く
        $response = $this->actingAs($user)->get(route('items.purchase.show', ['item_id' => $item->id]));
        $response->assertStatus(200);

        // 3. 初期状態で支払い方法が「-」と表示されていることを確認
        $response->assertSee('<span class="purchase__summary-value" id="selected-payment">-</span>', false);

        // 4. 支払い方法のプルダウンに「カード支払い」が含まれていることを確認
        $response->assertSee($paymentMethod->name);

        // 5. JavaScriptのコードが正しく含まれていることを確認
        // payment_method_idがカード支払いのIDにマッピングされていることを確認
        $response->assertSee("'{$paymentMethod->id}': '{$paymentMethod->name}'", false);

        // 6. JavaScriptのchangeイベントリスナーが設定されていることを確認
        $response->assertSee('paymentSelect.addEventListener(\'change\'', false);
        $response->assertSee('selectedPayment.textContent = paymentMethods[selectedId]', false);
    }
}
