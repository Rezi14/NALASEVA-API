<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Polyclinic;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DoctorSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password123');
        $now = Carbon::now();

        $doctorsData = [
            [
                'user' => [
                    'name' => 'dr. Andi Pratama',
                    'email' => 'dr.andi@puskesmas.go.id',
                    'password' => $password,
                    'role' => 'doctor',
                    'phone' => '081234567801',
                    'address' => 'Jl. Mawar No. 12, Kota Jambi',
                    'national_id' => '3601020304050001',
                    'gender' => 'Laki-laki',
                    'birth_date' => '1985-05-12',
                ],
                'doctor' => [
                    'specialization' => 'Dokter Umum',
                    'license_number' => 'SIP.123/UMUM/2020',
                    'is_online' => true,
                ],
                'poly_code' => 'POL-UMM',
            ],
            [
                'user' => [
                    'name' => 'dr. Siti Aminah',
                    'email' => 'dr.siti@puskesmas.go.id',
                    'password' => $password,
                    'role' => 'doctor',
                    'phone' => '081234567802',
                    'address' => 'Jl. Melati No. 8, Kota Jambi',
                    'national_id' => '3601020304050002',
                    'gender' => 'Perempuan',
                    'birth_date' => '1988-10-22',
                ],
                'doctor' => [
                    'specialization' => 'Spesialis Anak / KIA',
                    'license_number' => 'SIP.456/KIA/2021',
                    'is_online' => true,
                ],
                'poly_code' => 'POL-KIA',
            ],
            [
                'user' => [
                    'name' => 'drg. Budi Santoso',
                    'email' => 'drg.budi@puskesmas.go.id',
                    'password' => $password,
                    'role' => 'doctor',
                    'phone' => '081234567803',
                    'address' => 'Jl. Dahlia No. 4, Kota Jambi',
                    'national_id' => '3601020304050003',
                    'gender' => 'Laki-laki',
                    'birth_date' => '1982-03-15',
                ],
                'doctor' => [
                    'specialization' => 'Dokter Gigi',
                    'license_number' => 'SIP.789/GIGI/2019',
                    'is_online' => true,
                ],
                'poly_code' => 'POL-GIG',
            ],
            [
                'user' => [
                    'name' => 'dr. Rina Wijaya',
                    'email' => 'dr.rina@puskesmas.go.id',
                    'password' => $password,
                    'role' => 'doctor',
                    'phone' => '081234567804',
                    'address' => 'Jl. Anggrek No. 15, Kota Jambi',
                    'national_id' => '3601020304050004',
                    'gender' => 'Perempuan',
                    'birth_date' => '1990-12-05',
                ],
                'doctor' => [
                    'specialization' => 'Spesialis Anak',
                    'license_number' => 'SIP.101/ANAK/2022',
                    'is_online' => true,
                ],
                'poly_code' => 'POL-ANK',
            ],
        ];

        foreach ($doctorsData as $data) {
            $polyclinic = Polyclinic::where('code', $data['poly_code'])->first();
            if (!$polyclinic) {
                continue;
            }

            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $data['user']['email']],
                $data['user']
            );

            // Create or update doctor
            Doctor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'polyclinic_id' => $polyclinic->id,
                    'specialization' => $data['doctor']['specialization'],
                    'license_number' => $data['doctor']['license_number'],
                    'is_online' => $data['doctor']['is_online'],
                ]
            );
        }
    }
}
