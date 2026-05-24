<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Queue;
use App\Models\Polyclinic;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use ApiResponse;

    /**
     * Mengambil rangkuman statistik untuk dashboard admin.
     */
    public function getStats(Request $request)
    {
        try {
            $today = Carbon::today()->toDateString();

            // 1. Hitung total pasien & dokter terdaftar
            $totalPatients = User::where('role', 'patient')->count();
            $totalDoctors = Doctor::count();

            // 2. Hitung statistik antrean hari ini berdasarkan statusnya
            $activeQueuesToday = Queue::where('date', $today)
                ->whereIn('status', ['booked', 'waiting', 'examining'])
                ->count();

            $completedQueuesToday = Queue::where('date', $today)
                ->where('status', 'completed')
                ->count();

            $cancelledQueuesToday = Queue::where('date', $today)
                ->where('status', 'cancelled')
                ->count();

            // 3. Ambil statistik antrean per-poliklinik hari ini dengan 1 query tunggal (mencegah N+1)
            $todayQueues = Queue::where('date', $today)->get();
            $polyclinics = Polyclinic::all();

            $polyclinicStats = $polyclinics->map(function ($poly) use ($todayQueues) {
                $polyQueues = $todayQueues->where('polyclinic_id', $poly->id);
                return [
                    'polyclinic_id' => $poly->id,
                    'name' => $poly->name,
                    'active_queue_count' => $polyQueues->whereIn('status', ['booked', 'waiting', 'examining'])->count(),
                    'waiting_queue_count' => $polyQueues->where('status', 'waiting')->count(),
                ];
            });

            $stats = [
                'total_patients' => $totalPatients,
                'total_doctors' => $totalDoctors,
                'active_queues_today' => $activeQueuesToday,
                'completed_queues_today' => $completedQueuesToday,
                'cancelled_queues_today' => $cancelledQueuesToday,
                'polyclinic_stats' => $polyclinicStats
            ];

            return $this->successResponse($stats, 'Statistik dashboard berhasil diambil');

        } catch (\Exception $e) {
            return $this->errorResponse('Gagal mengambil statistik dashboard: ' . $e->getMessage(), 500);
        }
    }
}
