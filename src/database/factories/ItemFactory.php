<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\MasterData;

class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // condition_idは既存のMasterDataから取得、なければ作成
        $condition = MasterData::firstOrCreate([
            'type' => 'condition',
            'name' => '良好',
        ]);

        return [
            'name' => $this->faker->words(3, true),
            'image_path' => null,
            'is_sold' => false,
            'user_id' => User::factory(),
            'brand_id' => null,
            'price' => $this->faker->numberBetween(100, 100000),
            'description' => $this->faker->sentence(),
            'condition_id' => $condition->id,
        ];
    }
}
