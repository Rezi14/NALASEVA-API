<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medicines = [
            [
                'name' => 'Paracetamol 500mg',
                'stock' => 1000,
                'unit' => 'tablet',
            ],
            [
                'name' => 'Amoxicillin 500mg',
                'stock' => 500,
                'unit' => 'tablet',
            ],
            [
                'name' => 'Ibuprofen 400mg',
                'stock' => 300,
                'unit' => 'tablet',
            ],
            [
                'name' => 'CTM (Chlorpheniramine)',
                'stock' => 1200,
                'unit' => 'tablet',
            ],
            [
                'name' => 'Cetirizine 10mg',
                'stock' => 400,
                'unit' => 'tablet',
            ],
            [
                'name' => 'Antasida Doen',
                'stock' => 600,
                'unit' => 'tablet',
            ],
            [
                'name' => 'Vitamin C 500mg',
                'stock' => 800,
                'unit' => 'tablet',
            ],
            [
                'name' => 'OBH (Obat Batuk Hitam) Sirup',
                'stock' => 150,
                'unit' => 'botol',
            ],
            [
                'name' => 'Amoxicillin Sirup 125mg/5ml',
                'stock' => 100,
                'unit' => 'botol',
            ],
        ];

        foreach ($medicines as $medicine) {
            Medicine::updateOrCreate(
                ['name' => $medicine['name']],
                $medicine
            );
        }
    }
}
