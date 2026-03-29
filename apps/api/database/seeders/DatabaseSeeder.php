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

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        \Illuminate\Support\Facades\DB::table('products')->insert([
            [
                'id' => 'prod_001',
                'sku' => 'TV-001',
                'name' => 'Televisor Samsung 55',
                'category' => 'electronics',
                'unit_of_measure' => 'unit',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        \Illuminate\Support\Facades\DB::table('locations')->insert([
            [
                'id' => 'loc_001',
                'warehouse_code' => 'WH1',
                'zone' => 'A',
                'aisle' => '01',
                'rack' => '01',
                'level' => '1',
                'bin' => '1',
                'label' => 'WH1-A-01-01-1-1',
                'is_blocked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'loc_002',
                'warehouse_code' => 'WH1',
                'zone' => 'A',
                'aisle' => '02',
                'rack' => '01',
                'level' => '1',
                'bin' => '1',
                'label' => 'WH1-A-02-01-1-1',
                'is_blocked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        \Illuminate\Support\Facades\DB::table('stock_items')->insert([
            [
                'id' => 'stock_001',
                'product_id' => 'prod_001',
                'location_id' => 'loc_001',
                'quantity_on_hand' => 10,
                'quantity_available' => 8,
                'quantity_blocked' => 2,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
