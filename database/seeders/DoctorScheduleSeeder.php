<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Seeder;

class DoctorScheduleSeeder extends Seeder
{
    public function run(): void
    {
        // Get the seeded doctors
        $doctors = Doctor::with('user')->get();

        $scheduleTemplates = [
            'dr. Andi Pratama' => [
                ['day_of_week' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Selasa', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Rabu', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Kamis', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Jumat', 'start_time' => '08:00:00', 'end_time' => '11:00:00'],
            ],
            'dr. Siti Aminah' => [
                ['day_of_week' => 'Senin', 'start_time' => '13:00:00', 'end_time' => '16:00:00'],
                ['day_of_week' => 'Rabu', 'start_time' => '13:00:00', 'end_time' => '16:00:00'],
                ['day_of_week' => 'Jumat', 'start_time' => '13:00:00', 'end_time' => '16:00:00'],
            ],
            'drg. Budi Santoso' => [
                ['day_of_week' => 'Senin', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Rabu', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Kamis', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
            ],
            'dr. Rina Wijaya' => [
                ['day_of_week' => 'Selasa', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Kamis', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Sabtu', 'start_time' => '08:00:00', 'end_time' => '11:00:00'],
            ],
            'dr. Hendra Kusuma' => [
                ['day_of_week' => 'Selasa', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
                ['day_of_week' => 'Kamis', 'start_time' => '08:00:00', 'end_time' => '12:00:00'],
            ],
        ];

        foreach ($doctors as $doctor) {
            $name = $doctor->user->name;
            if (isset($scheduleTemplates[$name])) {
                foreach ($scheduleTemplates[$name] as $sched) {
                    DoctorSchedule::updateOrCreate(
                        [
                            'doctor_id' => $doctor->id,
                            'day_of_week' => $sched['day_of_week'],
                        ],
                        [
                            'start_time' => $sched['start_time'],
                            'end_time' => $sched['end_time'],
                        ]
                    );
                }
            }
        }
    }
}
