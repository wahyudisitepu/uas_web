<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->create([
            'name' => 'wahyudi',
            'email' => 'mr@tepu.com',
            'username' => 'wahyudi2',
            'is_staff' => true,
            'password' => Hash::make('rahasia'),
        ]);

        $this->call([
            QuestionBankSeeder::class,
        ]);
    }
}
