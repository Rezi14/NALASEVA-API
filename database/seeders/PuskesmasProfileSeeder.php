<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PuskesmasProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('puskesmas_profiles')->insert([
            'id' => 1,
            'name' => 'Puskesmas PBM',
            'address' => 'Jl. Kalimantan no 30, Jember',
            'phone' => '021-1234567',
            'email' => 'info@puskesmaspbm.go.id',
            'latitude' => -8.165143,
            'longitude' => 113.716255,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
