<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\SoldItem;
use App\Models\User;
use App\Models\Item;
use App\Models\MasterData;

class SoldItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = SoldItem::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // payment_method_idは既存のMasterDataから取得、なければ作成
        $paymentMethod = MasterData::firstOrCreate([
            'type' => 'payment_method',
            'name' => 'コンビニ支払い',
        ]);

        return [
            'item_id' => Item::factory(),
            'user_id' => User::factory(),
            'payment_method_id' => $paymentMethod->id,
            'shipping_postal_code' => $this->faker->numerify('###-####'),
            'shipping_address' => $this->faker->address(),
            'shipping_building_name' => $this->faker->optional()->buildingNumber(),
            'stripe_session_id' => null,
        ];
    }
}
