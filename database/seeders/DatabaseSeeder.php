<?php

namespace Database\Seeders;
use Illuminate\Support\Facades\Hash;
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
        // Membuat akun admin otomatis
        User::create([
            'name' => 'Saya Admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin123'), // Password kamu
            'role' => 'admin',
        ]);
    }
}
