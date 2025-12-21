<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'name' => '田中太郎',
                'email' => 'taro@example.com',
                'postal_code' => '100-0001',
                'address' => '東京都千代田区千代田1-1',
            ],
            [
                'name' => '佐藤花子',
                'email' => 'hanako@example.com',
                'postal_code' => '150-0001',
                'address' => '東京都渋谷区神宮前1-1-1',
            ],
            [
                'name' => '鈴木次郎',
                'email' => 'jiro@example.com',
                'postal_code' => '530-0001',
                'address' => '大阪府大阪市北区梅田1-1-1',
            ],
            [
                'name' => '高橋三奈',
                'email' => 'mina@example.com',
                'postal_code' => '460-0008',
                'address' => '愛知県名古屋市中区栄1-1-1',
            ],
            [
                'name' => '山本四郎',
                'email' => 'shiro@gmail.com',
                'postal_code' => '060-0001',
                'address' => '北海道札幌市中央区北一条西1-1-1',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => Hash::make('password'),
                    'postal_code' => $user['postal_code'],
                    'address' => $user['address'],
                    'email_verified_at' => now(),
                ]
            );
        }
    }
}
