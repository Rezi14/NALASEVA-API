<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PharmacistSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $password = Hash::make('password123');

        \App\Models\User::updateOrCreate(
            ['email' => 'apoteker@apoteker.com'],
            [
                'name' => 'Apoteker Utama Puskesmas', 
                'password' => $password, 
                'role' => 'pharmacist', 
                'phone' => '089876543210',
                'address' => 'Jl. Farmasi No. 1, Puskesmas Apotek',
                'national_id' => '9876543210987654',
                'gender' => 'Perempuan',
                'birth_date' => '1992-05-15',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'apoteker2@apoteker.com'],
            [
                'name' => 'Apoteker Asisten NalaSeva', 
                'password' => $password, 
                'role' => 'pharmacist', 
                'phone' => '089876543211',
                'address' => 'Jl. Resep Obat No. 2, Puskesmas Apotek',
                'national_id' => '9876543210987655',
                'gender' => 'Laki-laki',
                'birth_date' => '1995-11-10',
            ]
        );
    }
}
