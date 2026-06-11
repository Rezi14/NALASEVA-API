<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Traits\ApiResponse;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Exception;

class PaymentController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Payment::with(['queue.polyclinic', 'queue.patient.user', 'examination.prescriptionItems.medicine']);

        if ($user->role === 'patient') {
            $query->whereHas('queue', function ($q) use ($user) {
                $q->whereHas('patient', function ($p) use ($user) {
                    $p->where('user_id', $user->id);
                });
            });
        }

        return $this->successResponse(PaymentResource::collection($query->get()), 'Daftar pembayaran berhasil diambil');
    }

    public function show(Request $request, $id)
    {
        try {
            $payment = Payment::with(['queue.polyclinic', 'queue.patient.user', 'examination.prescriptionItems.medicine'])
                ->findOrFail($id);

            $user = $request->user();
            if ($user->role === 'patient') {
                if ($payment->queue->patient->user_id !== $user->id) {
                    return $this->errorResponse('Akses ditolak. Tagihan ini bukan milik Anda.', 403);
                }
            }

            return $this->successResponse(new PaymentResource($payment), 'Detail pembayaran ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data pembayaran tidak ditemukan', 404);
        }
    }

    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $payment = Payment::findOrFail($id);
            $user = $request->user();

            if ($user->role === 'patient') {
                if ($payment->queue->patient->user_id !== $user->id) {
                    return $this->errorResponse('Akses ditolak. Tagihan ini bukan milik Anda.', 403);
                }
            }

            if ($payment->status === 'paid') {
                return $this->errorResponse('Pembayaran sudah lunas.', 400);
            }

            if ($request->hasFile('payment_proof')) {
                $file = $request->file('payment_proof');

                // Encode ke Base64 dan simpan langsung ke kolom payment_proof di database.
                // Railway menggunakan ephemeral filesystem (file hilang saat restart),
                // sehingga Base64 di DB adalah satu-satunya cara yang andal dan persisten.
                $mimeType = $file->getMimeType();
                $base64Data = base64_encode(file_get_contents($file->getRealPath()));
                $dataUri = "data:{$mimeType};base64,{$base64Data}";

                $payment->update([
                    'payment_proof' => $dataUri,
                    'status' => 'waiting_verification',
                ]);

                return $this->successResponse(new PaymentResource($payment), 'Bukti pembayaran berhasil diunggah, menunggu verifikasi admin.');
            }

            return $this->errorResponse('File bukti pembayaran tidak ditemukan.', 400);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengunggah bukti pembayaran: ' . $e->getMessage(), 500);
        }
    }

    public function verify(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:paid,failed',
        ]);

        try {
            $payment = Payment::findOrFail($id);

            if ($payment->status === 'paid') {
                return $this->errorResponse('Pembayaran sudah lunas sebelumnya.', 400);
            }

            $status = $request->status;
            $updateData = ['status' => $status];

            if ($status === 'paid') {
                $updateData['paid_at'] = now();
            }

            $payment->update($updateData);

            // Kirim notifikasi pembayaran terverifikasi via FCM jika status paid
            if ($status === 'paid') {
                $payment->load('queue.patient.user');
                $patientToken = $payment->queue?->patient?->user?->fcm_token ?? null;
                if ($patientToken) {
                    try {
                        $firebaseService = new \App\Services\FirebaseNotificationService();
                        $title = "Pembayaran Terverifikasi Lunas!";
                        $body = "Pembayaran tagihan Anda berhasil terverifikasi. Resep obat Anda telah dikirim ke loket Apotek Puskesmas.";
                        $firebaseService->sendToToken($patientToken, $title, $body, [
                            'payment_id' => $payment->id,
                            'status' => 'paid',
                            'type' => 'payment_updated'
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('FCM Verify Payment Notification Error: ' . $e->getMessage(), [
                            'payment_id' => $payment->id,
                            'exception' => $e
                        ]);
                    }
                }
            }

            return $this->successResponse(new PaymentResource($payment), 'Status pembayaran berhasil diverifikasi menjadi ' . $status);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal melakukan verifikasi pembayaran: ' . $e->getMessage(), 500);
        }
    }

    public function cashPay(Request $request, $id)
    {
        try {
            $payment = Payment::findOrFail($id);

            if ($payment->status === 'paid') {
                return $this->errorResponse('Pembayaran sudah lunas sebelumnya.', 400);
            }

            $payment->update([
                'status' => 'paid',
                'payment_method' => 'cash',
                'paid_at' => now(),
            ]);

            // Kirim notifikasi pembayaran lunas (Tunai) via FCM
            $payment->load('queue.patient.user');
            $patientToken = $payment->queue?->patient?->user?->fcm_token ?? null;
            if ($patientToken) {
                try {
                    $firebaseService = new \App\Services\FirebaseNotificationService();
                    $title = "Pembayaran Lunas (Tunai)!";
                    $body = "Pembayaran tunai Anda berhasil dicatat lunas. Resep obat Anda telah dikirim ke loket Apotek Puskesmas.";
                    $firebaseService->sendToToken($patientToken, $title, $body, [
                        'payment_id' => $payment->id,
                        'status' => 'paid',
                        'type' => 'payment_updated'
                    ]);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('FCM Cash Payment Notification Error: ' . $e->getMessage(), [
                        'payment_id' => $payment->id,
                        'exception' => $e
                    ]);
                }
            }

            return $this->successResponse(new PaymentResource($payment), 'Pembayaran tunai berhasil dicatat dan lunas.');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memproses pembayaran tunai: ' . $e->getMessage(), 500);
        }
    }

    public function getProofImage($id)
    {
        try {
            $payment = Payment::findOrFail($id);

            if (!$payment->payment_proof) {
                return response()->json(['message' => 'Bukti pembayaran belum diunggah.'], 404);
            }

            // payment_proof menyimpan data URI Base64: "data:image/jpeg;base64,XXXXX"
            if (str_starts_with($payment->payment_proof, 'data:image/')) {
                preg_match('/^data:(image\/\w+);base64,(.+)$/', $payment->payment_proof, $matches);
                if (count($matches) === 3) {
                    $mimeType = $matches[1];
                    $imageData = base64_decode($matches[2]);
                    return response($imageData, 200)
                        ->header('Content-Type', $mimeType)
                        ->header('Access-Control-Allow-Origin', '*')
                        ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
                }
            }

            // Fallback: format lama (path file di storage, mungkin masih ada)
            if (Storage::disk('public')->exists($payment->payment_proof)) {
                $file = Storage::disk('public')->get($payment->payment_proof);
                $mimeType = Storage::disk('public')->mimeType($payment->payment_proof);
                return response($file, 200)
                    ->header('Content-Type', $mimeType)
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
            }

            return response()->json(['message' => 'File bukti pembayaran tidak dapat diakses.'], 404);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Data pembayaran tidak ditemukan.'], 404);
        } catch (Exception $e) {
            return response()->json(['message' => 'Gagal mengambil bukti pembayaran: ' . $e->getMessage()], 500);
        }
    }
}
