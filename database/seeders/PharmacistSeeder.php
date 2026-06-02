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
                'address' => 'Jl. Farmasi No. 1, Apotek NalaSeva',
                'national_id' => '9876543210987654',
                'gender' => 'Perempuan',
                'birth_date' => '1992-05-15',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'apoteker2@apoteker.com'],
            [
                'name' => 'Apoteker Kedua NalaSeva', 
                'password' => $password, 
                'role' => 'pharmacist', 
                'phone' => '089876543211',
                'address' => 'Jl. Resep Obat No. 2, Apotek NalaSeva',
                'national_id' => '9876543210987655',
                'gender' => 'Laki-laki',
                'birth_date' => '1995-11-10',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'apoteker3@apoteker.com'],
            [
                'name' => 'Apoteker Ketiga NalaSeva', 
                'password' => $password, 
                'role' => 'pharmacist', 
                'phone' => '089876543212',
                'address' => 'Jl. Obat Manjur No. 3, Apotek NalaSeva',
                'national_id' => '9876543210987656',
                'gender' => 'Perempuan',
                'birth_date' => '1993-02-18',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'apoteker4@apoteker.com'],
            [
                'name' => 'Apoteker Keempat NalaSeva', 
                'password' => $password, 
                'role' => 'pharmacist', 
                'phone' => '089876543213',
                'address' => 'Jl. Kapsul Sehat No. 4, Apotek NalaSeva',
                'national_id' => '9876543210987657',
                'gender' => 'Laki-laki',
                'birth_date' => '1996-07-25',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'apoteker5@apoteker.com'],
            [
                'name' => 'Apoteker Kelima NalaSeva', 
                'password' => $password, 
                'role' => 'pharmacist', 
                'phone' => '089876543214',
                'address' => 'Jl. Racikan No. 5, Apotek NalaSeva',
                'national_id' => '9876543210987658',
                'gender' => 'Perempuan',
                'birth_date' => '1994-10-02',
            ]
        );
    }
}
