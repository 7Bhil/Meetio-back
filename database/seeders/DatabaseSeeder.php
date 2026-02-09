<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Création sécurisée de l'admin (ne fait rien si l'email existe déjà)
        User::firstOrCreate(
            ['email' => '7bhilal.chitou7@gmail.com'],
            [
                'name' => 'Admin Meetio',
                'password' => \Illuminate\Support\Facades\Hash::make('Bh7777777'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
