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
                'price' => 500.00,
            ],
            [
                'name' => 'Amoxicillin 500mg',
                'stock' => 500,
                'unit' => 'tablet',
                'price' => 1200.00,
            ],
            [
                'name' => 'Ibuprofen 400mg',
                'stock' => 300,
                'unit' => 'tablet',
                'price' => 800.00,
            ],
            [
                'name' => 'CTM (Chlorpheniramine)',
                'stock' => 1200,
                'unit' => 'tablet',
                'price' => 200.00,
            ],
            [
                'name' => 'Cetirizine 10mg',
                'stock' => 400,
                'unit' => 'tablet',
                'price' => 1000.00,
            ],
            [
                'name' => 'Antasida Doen',
                'stock' => 600,
                'unit' => 'tablet',
                'price' => 400.00,
            ],
            [
                'name' => 'Vitamin C 500mg',
                'stock' => 800,
                'unit' => 'tablet',
                'price' => 600.00,
            ],
            [
                'name' => 'OBH (Obat Batuk Hitam) Sirup',
                'stock' => 150,
                'unit' => 'botol',
                'price' => 12000.00,
            ],
            [
                'name' => 'Amoxicillin Sirup 125mg/5ml',
                'stock' => 100,
                'unit' => 'botol',
                'price' => 15000.00,
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
