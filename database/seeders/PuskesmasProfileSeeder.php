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
            'name' => 'Puskesmas Sehat Utama',
            'address' => 'Jl. Raya Sehat No. 12, Jakarta',
            'phone' => '021-1234567',
            'email' => 'info@puskesmassehat.go.id',
            'latitude' => -6.175392,
            'longitude' => 106.827153,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
