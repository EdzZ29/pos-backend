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
        // Create roles
        \App\Models\Role::insert([
            ['name' => 'Owner',   'slug' => 'owner',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manager', 'slug' => 'manager', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cashier', 'slug' => 'cashier', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Seed payment methods
        \App\Models\PaymentMethod::insert([
            ['name' => 'Cash',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'GCash', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Card',  'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create default owner account
        User::factory()->create([
            'role_id'  => 1,
            'name'     => 'Admin Owner',
            'username' => 'admin',
            'email'    => 'admin@pos.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);
    }
}
