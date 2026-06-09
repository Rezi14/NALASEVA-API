<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Medicine;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PharmacyController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        // Ambil resep obat dari pemeriksaan yang pembayarannya lunas (paid) dan belum diserahkan (dispensed_at null)
        $queues = Payment::with(['queue.polyclinic', 'queue.patient.user', 'examination.prescriptionItems.medicine'])
            ->where('status', 'paid')
            ->whereNull('dispensed_at')
            ->get();

        return $this->successResponse($queues, 'Daftar antrean resep obat apotek berhasil diambil');
    }

    public function dispense(Request $request, $paymentId)
    {
        try {
            return DB::transaction(function () use ($paymentId) {
                $payment = Payment::with(['examination.prescriptionItems.medicine'])->findOrFail($paymentId);

                if ($payment->status !== 'paid') {
                    return $this->errorResponse('Obat tidak dapat diserahkan karena pembayaran belum lunas.', 400);
                }

                if ($payment->dispensed_at !== null) {
                    return $this->errorResponse('Resep obat ini sudah diserahkan sebelumnya.', 400);
                }

                $examination = $payment->examination;
                if (!$examination || $examination->prescriptionItems->isEmpty()) {
                    return $this->errorResponse('Data resep obat tidak ditemukan.', 404);
                }

                // Validasi kecukupan stok obat terlebih dahulu
                foreach ($examination->prescriptionItems as $item) {
                    $medicine = $item->medicine;
                    if ($medicine->stock < $item->quantity) {
                        return $this->errorResponse("Stok obat '{$medicine->name}' tidak mencukupi. Sisa stok: {$medicine->stock}.", 422);
                    }
                }

                // Kurangi stok obat
                foreach ($examination->prescriptionItems as $item) {
                    $item->medicine->decrement('stock', $item->quantity);
                }

                // Tandai resep obat telah diserahkan
                $payment->update([
                    'dispensed_at' => now(),
                ]);

                // Kirim notifikasi obat selesai diserahkan via FCM
                $payment->load('queue.patient.user');
                $patientToken = $payment->queue?->patient?->user?->fcm_token ?? null;
                if ($patientToken) {
                    try {
                        $firebaseService = new \App\Services\FirebaseNotificationService();
                        $title = "Obat Selesai Diserahkan";
                        $body = "Obat Anda telah sukses diserahkan oleh Apoteker. Terima kasih, semoga lekas sembuh!";
                        $firebaseService->sendToToken($patientToken, $title, $body, [
                            'payment_id' => $payment->id,
                            'status' => 'dispensed',
                            'type' => 'prescription_updated'
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('FCM Dispense Notification Error: ' . $e->getMessage(), [
                            'payment_id' => $payment->id,
                            'exception' => $e
                        ]);
                    }
                }

                return $this->successResponse($payment, 'Resep obat berhasil diserahkan dan stok obat diperbarui.');
            });
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menyerahkan resep obat: ' . $e->getMessage(), 500);
        }
    }
}
