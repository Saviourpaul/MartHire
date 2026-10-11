<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'saviourpaul24@gmail.com')],
            [
                'name' => 'Administrator',
                'password' => Hash::make(env('ADMIN_PASSWORD', '123456789')),
                'role' => 'admin',
            ]
        );
    }
}
