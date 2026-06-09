<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Queue;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('queues:cancel-no-show', function () {
    $now = Carbon::now();
    $today = Carbon::today()->toDateString();
    
    // Cari antrean berstatus 'booked' atau 'waiting' yang estimasi waktunya sudah terlewat
    $noShowQueues = Queue::where('date', $today)
        ->whereIn('status', ['booked', 'waiting'])
        ->whereNotNull('estimated_service_time')
        ->get();
        
    $cancelledCount = 0;
    foreach ($noShowQueues as $queue) {
        $estimatedTime = strlen($queue->estimated_service_time) > 5
            ? Carbon::createFromFormat('H:i:s', $queue->estimated_service_time)
            : Carbon::createFromFormat('H:i', $queue->estimated_service_time);
        // Selisih menit antara waktu sekarang dengan estimasi waktu
        // Jika negatif, berarti waktu estimasi sudah terlewat (misal: estimasi 08:00, sekarang 08:45 -> diff = -45)
        $diff = $now->diffInMinutes($estimatedTime, false);
        
        // jika terlewat > 30 menit untuk booked, atau > 120 menit untuk waiting
        if ($queue->status === 'booked' && $diff < -30) {
            $queue->update(['status' => 'cancelled']);
            $cancelledCount++;
        } elseif ($queue->status === 'waiting' && $diff < -120) {
            $queue->update(['status' => 'cancelled']);
            $cancelledCount++;
        }
    }
    
    $this->info("Berhasil membatalkan {$cancelledCount} antrean no-show.");
})->purpose('Auto-cancel bookings where patient is no-show after 30 mins (booked) or 2 hours (waiting) from their estimated time');

// Jadwalkan perintah untuk berjalan setiap 5 menit
Schedule::command('queues:cancel-no-show')->everyFiveMinutes();

Artisan::command('payments:auto-expire', function () {
    $now = Carbon::now();
    $twoHoursAgo = $now->copy()->subHours(2);
    
    // Cari tagihan pending yang berumur > 2 jam
    $expiredPayments = \App\Models\Payment::where('status', 'pending')
        ->where('created_at', '<', $twoHoursAgo)
        ->get();
        
    $expiredCount = 0;
    foreach ($expiredPayments as $payment) {
        $payment->update(['status' => 'cancelled']);
        
        // Kirim notifikasi ke pasien via FCM bahwa tagihan kedaluwarsa
        $payment->load('queue.patient.user');
        $patientToken = $payment->queue?->patient?->user?->fcm_token ?? null;
        if ($patientToken) {
            try {
                $firebaseService = new \App\Services\FirebaseNotificationService();
                $title = "Tagihan Kadaluwarsa";
                $body = "Tagihan Anda dengan nomor transaksi " . $payment->transaction_number . " telah otomatis dibatalkan karena tidak dibayar dalam waktu 2 jam.";
                $firebaseService->sendToToken($patientToken, $title, $body, [
                    'payment_id' => $payment->id,
                    'status' => 'cancelled',
                    'type' => 'payment_updated'
                ]);
            } catch (\Exception $e) {
                Log::error('FCM Auto Expire Notification Error: ' . $e->getMessage(), [
                    'payment_id' => $payment->id,
                    'exception' => $e
                ]);
            }
        }
        $expiredCount++;
    }
    
    $this->info("Berhasil membatalkan {$expiredCount} tagihan tertunggak (kedaluwarsa).");
})->purpose('Auto-cancel pending payments older than 2 hours');

// Jadwalkan auto-expire setiap 5 menit
Schedule::command('payments:auto-expire')->everyFiveMinutes();

