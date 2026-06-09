<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\Queue;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreExaminationRequest;
use App\Http\Requests\UpdateExaminationRequest;
use App\Http\Resources\ExaminationResource;
use Illuminate\Http\Request;
use Exception;

class ExaminationController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        $query = Examination::with(['queue.polyclinic', 'queue.patient.user', 'doctor.user', 'payment', 'prescriptionItems.medicine']);
        $user = $request->user();

        if ($user->role === 'patient') {
            $query->whereHas('queue', function($q) use ($user) {
                $q->whereHas('patient', function($p) use ($user) {
                    $p->where('user_id', $user->id);
                });
            });
        } elseif ($user->role === 'doctor') {
            // Dokter hanya diperbolehkan melihat rekam medis dari polikliniknya sendiri
            $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
            if ($doctor) {
                $query->whereHas('queue', function($q) use ($doctor) {
                    $q->where('polyclinic_id', $doctor->polyclinic_id);
                });
            } else {
                $query->whereRaw('1 = 0');
            }

            if ($request->has('patient_user_id')) {
                $query->whereHas('queue', function($q) use ($request) {
                    $q->whereHas('patient', function($p) use ($request) {
                        $p->where('user_id', $request->patient_user_id);
                    });
                });
            }
        } elseif ($request->has('patient_user_id')) {
            $query->whereHas('queue', function($q) use ($request) {
                $q->whereHas('patient', function($p) use ($request) {
                    $p->where('user_id', $request->patient_user_id);
                });
            });
        }

        if ($request->has('page') || $request->has('per_page') || $request->has('paginate')) {
            $limit = $request->input('per_page', 20);
            return $this->successResponse(ExaminationResource::collection($query->paginate($limit)), 'Daftar rekam medis berhasil diambil');
        }

        return $this->successResponse(ExaminationResource::collection($query->get()), 'Daftar rekam medis berhasil diambil');
    }

    public function store(StoreExaminationRequest $request) {
        $user = $request->user();
        $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            return $this->errorResponse('Akses ditolak. Anda bukan dokter terdaftar.', 403);
        }

        $queue = Queue::findOrFail($request->queue_id);
        if ($queue->status !== 'examining') {
            return $this->errorResponse('Pasien belum dipanggil / antrean tidak dalam status pemeriksaan', 400);
        }

        // Cegah duplikasi rekam medis untuk antrean yang sama
        $existingExam = Examination::where('queue_id', $request->queue_id)->exists();
        if ($existingExam) {
            return $this->errorResponse('Rekam medis sudah tersedia untuk antrean ini. Tidak dapat membuat duplikat.', 422);
        }

        if ($queue->doctor_id !== $doctor->id) {
            return $this->errorResponse('Akses ditolak. Dokter yang login tidak cocok dengan dokter pada tiket antrean.', 403);
        }

        try {
            return \Illuminate\Support\Facades\DB::transaction(function() use ($request, $queue, $doctor) {
                $validated = $request->validated();
                $validated['doctor_id'] = $doctor->id; // Paksa menggunakan ID dokter yang sedang login

                $data = Examination::storeData($validated);

                // Simpan prescription items dan hitung total biaya obat jika ada
                $medicineFee = 0.00;
                if ($request->has('prescription_items')) {
                    foreach ($request->input('prescription_items') as $item) {
                        $medicine = \App\Models\Medicine::findOrFail($item['medicine_id']);
                        $price = $medicine->price;
                        
                        \App\Models\PrescriptionItem::create([
                            'examination_id' => $data->id,
                            'medicine_id' => $item['medicine_id'],
                            'quantity' => $item['quantity'],
                            'instruction' => $item['instruction'],
                            'price' => $price,
                        ]);

                        $medicineFee += $price * $item['quantity'];
                    }
                }

                // Update status antrean menjadi 'completed' secara otomatis
                $queue->update(['status' => 'completed']);

                // Buat tagihan pembayaran otomatis
                $regFee = (double)\App\Models\Setting::getValue('registration_fee', 10000.00);
                $totalAmount = $regFee + $medicineFee;
                $txNumber = 'NS-PAY-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6));

                $payment = \App\Models\Payment::create([
                    'queue_id' => $queue->id,
                    'examination_id' => $data->id,
                    'transaction_number' => $txNumber,
                    'registration_fee' => $regFee,
                    'medicine_fee' => $medicineFee,
                    'total_amount' => $totalAmount,
                    'payment_method' => 'transfer_bank',
                    'status' => 'pending',
                ]);

                // Kirim notifikasi tagihan baru ke pasien via FCM
                $patientToken = $queue->patient?->user?->fcm_token ?? null;
                if ($patientToken) {
                    try {
                        $firebaseService = new \App\Services\FirebaseNotificationService();
                        $title = "Tagihan Baru Diterbitkan";
                        $body = "Pemeriksaan selesai. Silakan lakukan pembayaran QRIS sebesar Rp" . number_format($totalAmount, 0, ',', '.') . " untuk mengambil obat.";
                        $firebaseService->sendToToken($patientToken, $title, $body, [
                            'payment_id' => $payment->id,
                            'status' => 'pending',
                            'type' => 'payment_updated'
                        ]);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('FCM New Invoice Notification Error: ' . $e->getMessage(), [
                            'payment_id' => $payment->id,
                            'exception' => $e
                        ]);
                    }
                }

                $data->load(['queue.polyclinic', 'queue.patient.user', 'doctor.user', 'prescriptionItems.medicine']);
                return $this->successResponse(new ExaminationResource($data), 'Data pemeriksaan dan tagihan pembayaran berhasil disimpan', 201);
            });
        } catch (\Exception $e) {
            return $this->errorResponse('Gagal menyimpan rekam medis: ' . $e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id) {
        try {
            $examination = Examination::getById($id);
            
            $user = $request->user();
            if ($user->role === 'patient') {
                if ($examination->queue->patient->user_id !== $user->id) {
                    return $this->errorResponse('Akses ditolak. Rekam medis ini bukan milik Anda.', 403);
                }
            } elseif ($user->role === 'doctor') {
                $doctor = \App\Models\Doctor::where('user_id', $user->id)->first();
                if (!$doctor || $examination->queue->polyclinic_id !== $doctor->polyclinic_id) {
                    return $this->errorResponse('Akses ditolak. Rekam medis ini tidak berada di poliklinik Anda.', 403);
                }
            }
            
            return $this->successResponse(new ExaminationResource($examination), 'Detail rekam medis ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data rekam medis tidak ditemukan', 404);
        }
    }

    public function update(UpdateExaminationRequest $request, $id) {
        try {
            // BUG-12 Security Fix: Hanya Dokter dan Admin yang bisa mengubah rekam medis
            if ($request->user()->role === 'patient') {
                return $this->errorResponse('Akses ditolak. Pasien tidak diizinkan mengubah rekam medis.', 403);
            }

            $examination = Examination::findOrFail($id);
            if ($request->user()->role === 'doctor') {
                $doctor = \App\Models\Doctor::where('user_id', $request->user()->id)->first();
                if (!$doctor || $examination->doctor_id !== $doctor->id) {
                    return $this->errorResponse('Akses ditolak. Anda hanya diizinkan mengubah rekam medis yang Anda buat sendiri.', 403);
                }
            }

            $data = Examination::updateData($id, $request->validated());
            $data->load(['queue.polyclinic', 'queue.patient.user', 'doctor.user']);
            return $this->successResponse(new ExaminationResource($data), 'Data rekam medis berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data pemeriksaan tidak ditemukan', 404);
        }
    }

    public function destroy(Request $request, $id) {
        try {
            if ($request->user()->role === 'patient') {
                return $this->errorResponse('Akses ditolak. Pasien tidak diizinkan menghapus rekam medis.', 403);
            }

            $examination = Examination::findOrFail($id);
            if ($request->user()->role === 'doctor') {
                $doctor = \App\Models\Doctor::where('user_id', $request->user()->id)->first();
                if (!$doctor || $examination->doctor_id !== $doctor->id) {
                    return $this->errorResponse('Akses ditolak. Anda hanya diizinkan menghapus rekam medis yang Anda buat sendiri.', 403);
                }
            }

            Examination::softDeleteData($id);
            return $this->successResponse(null, 'Data pemeriksaan berhasil dihapus');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus, data pemeriksaan tidak ditemukan', 404);
        }
    }

    public function restore($id) {
        try {
            Examination::restoreData($id);
            return $this->successResponse(null, 'Data pemeriksaan berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }
}