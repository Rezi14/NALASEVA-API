<?php

namespace Database\Seeders;

use App\Models\Polyclinic;
use Illuminate\Database\Seeder;

class PolyclinicSeeder extends Seeder
{
    public function run(): void
    {
        $polyclinics = [
            [
                'code' => 'POL-UMM',
                'name' => 'Poli Umum',
                'description' => 'Pelayanan pemeriksaan kesehatan umum dan pengobatan dasar.',
            ],
            [
                'code' => 'POL-GIG',
                'name' => 'Poli Gigi',
                'description' => 'Pelayanan kesehatan gigi dan mulut, penambalan, pencabutan, dan pembersihan karang gigi.',
            ],
            [
                'code' => 'POL-KIA',
                'name' => 'Poli KIA & KB',
                'description' => 'Pelayanan kesehatan ibu dan anak, imunisasi, serta keluarga berencana.',
            ],
            [
                'code' => 'POL-ANK',
                'name' => 'Poli Anak',
                'description' => 'Spesialisasi pelayanan kesehatan anak.',
            ],
            [
                'code' => 'POL-LNS',
                'name' => 'Poli Lansia',
                'description' => 'Pelayanan khusus kesehatan pasien lanjut usia.',
            ],
        ];

        foreach ($polyclinics as $poly) {
            Polyclinic::updateOrCreate(
                ['code' => $poly['code']],
                [
                    'name' => $poly['name'],
                    'description' => $poly['description']
                ]
            );
        }
    }
}
