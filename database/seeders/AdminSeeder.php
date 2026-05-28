<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class KlinikSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $password = Hash::make('password');

        //  Insert Admin
        DB::table('users')->insert([
            [
                'name' => 'Admin Puskesmas', 
                'email' => 'admin@gmail.com', 
                'password' => $password, 
                'role' => 'admin', 
                'phone' => '081234567890',
                'address' => 'Jl. Kesehatan No. 1, Puskesmas Admin',
                'national_id' => '1234567890123456',
                'gender' => 'Laki-laki',
                'birth_date' => '1990-01-01',
                'created_at' => $now, 
                'updated_at' => $now
            ],
        ]);
    }
}

