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

        //  Insert 15 Users
        DB::table('users')->insert([
            ['name' => 'Admin Puskesmas', 'email' => 'admin@gmail.com', 'password' => $password, 'role' => 'admin', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }
}

