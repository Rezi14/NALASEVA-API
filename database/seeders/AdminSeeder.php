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
                'name' => 'Admin Utama NalaSeva', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567890',
                'address' => 'Jl. Kesehatan No. 1, Puskesmas NalaSeva',
                'national_id' => '1234567890123456',
                'gender' => 'Laki-laki',
                'birth_date' => '1990-01-01',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'admin2@admin.com'],
            [
                'name' => 'Admin Kedua NalaSeva', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567891',
                'address' => 'Jl. Sehat Walafiat No. 2, Puskesmas NalaSeva',
                'national_id' => '1234567890123457',
                'gender' => 'Perempuan',
                'birth_date' => '1993-08-20',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'admin3@admin.com'],
            [
                'name' => 'Admin Ketiga NalaSeva', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567892',
                'address' => 'Jl. Bhakti Husada No. 3, Puskesmas NalaSeva',
                'national_id' => '1234567890123458',
                'gender' => 'Laki-laki',
                'birth_date' => '1991-04-12',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'admin4@admin.com'],
            [
                'name' => 'Admin Keempat NalaSeva', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567893',
                'address' => 'Jl. Kebugaran No. 4, Puskesmas NalaSeva',
                'national_id' => '1234567890123459',
                'gender' => 'Perempuan',
                'birth_date' => '1994-09-05',
            ]
        );

        \App\Models\User::updateOrCreate(
            ['email' => 'admin5@admin.com'],
            [
                'name' => 'Admin Kelima NalaSeva', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567894',
                'address' => 'Jl. Sanitasi No. 5, Puskesmas NalaSeva',
                'national_id' => '1234567890123460',
                'gender' => 'Laki-laki',
                'birth_date' => '1988-12-30',
            ]
        );
    }
}

