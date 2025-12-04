<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['id' => 'admin'],
            [
                'name'     => '관리자',
                'password' => Hash::make('admin1234'),
            ]
        );
    }
}
