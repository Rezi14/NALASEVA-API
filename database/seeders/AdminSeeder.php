<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $password = Hash::make('password123');

        \App\Models\User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin Utama Puskesmas', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567890',
                'address' => 'Jl. Kesehatan No. 1, Puskesmas Admin',
                'national_id' => '1234567890123456',
                'gender' => 'Laki-laki',
                'birth_date' => '1990-01-01',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'admin2@admin.com'],
            [
                'name' => 'Admin Asisten NalaSeva', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567891',
                'address' => 'Jl. Sehat Walafiat No. 2, Puskesmas Admin',
                'national_id' => '1234567890123457',
                'gender' => 'Perempuan',
                'birth_date' => '1993-08-20',
            ]
        );
    }
}

