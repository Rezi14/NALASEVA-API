<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Examination;
use App\Models\Queue;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class ExaminationController extends Controller
{
    use ApiResponse;

    public function index(Request $request) {
        $query = Examination::with(['queue.polyclinic', 'queue.patient.user', 'doctor.user']);
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

        return $this->successResponse($query->get(), 'Daftar rekam medis berhasil diambil');
    }

    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'queue_id'  => 'required|integer|exists:queues,id',
            'doctor_id' => 'required|integer|exists:doctors,id',
            'complaint' => 'required|string',
            'diagnosis' => 'required|string',
            'treatment' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $queue = Queue::findOrFail($request->queue_id);
        if ($queue->status !== 'examining') {
            return $this->errorResponse('Pasien belum dipanggil / antrean tidak dalam status pemeriksaan', 400);
        }

        try {
            return \Illuminate\Support\Facades\DB::transaction(function() use ($validator, $queue) {
                $data = Examination::storeData($validator->validated());

                // Update status antrean menjadi 'completed' secara otomatis
                $queue->update(['status' => 'completed']);

                return $this->successResponse($data, 'Data pemeriksaan berhasil disimpan', 201);
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
            
            return $this->successResponse($examination, 'Detail rekam medis ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data rekam medis tidak ditemukan', 404);
        }
    }

    public function update(Request $request, $id) {
        try {
            $validator = Validator::make($request->all(), [
                'queue_id'  => 'sometimes|required|integer|exists:queues,id',
                'doctor_id' => 'sometimes|required|integer|exists:doctors,id',
                'complaint' => 'sometimes|required|string',
                'diagnosis' => 'sometimes|required|string',
                'treatment' => 'sometimes|required|string'
            ]);

            // BUG-12 Security Fix: Hanya Dokter dan Admin yang bisa mengubah rekam medis
            if ($request->user()->role === 'patient') {
                return $this->errorResponse('Akses ditolak. Pasien tidak diizinkan mengubah rekam medis.', 403);
            }

            if ($validator->fails()) {
                return $this->errorResponse($validator->errors()->first(), 422);
            }

            $examination = Examination::findOrFail($id);
            if ($request->user()->role === 'doctor') {
                $doctor = \App\Models\Doctor::where('user_id', $request->user()->id)->first();
                if (!$doctor || $examination->queue->polyclinic_id !== $doctor->polyclinic_id) {
                    return $this->errorResponse('Akses ditolak. Anda tidak diizinkan mengubah rekam medis dari poliklinik lain.', 403);
                }
            }

            $data = Examination::updateData($id, $validator->validated());
            return $this->successResponse($data, 'Data rekam medis berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui, data pemeriksaan tidak ditemukan', 404);
        }
    }

    public function destroy(Request $request, $id) {
        try {
            // BUG-12 Security Fix: Hanya Dokter dan Admin yang bisa menghapus rekam medis
            if ($request->user()->role === 'patient') {
                return $this->errorResponse('Akses ditolak. Pasien tidak diizinkan menghapus rekam medis.', 403);
            }

            $examination = Examination::findOrFail($id);
            if ($request->user()->role === 'doctor') {
                $doctor = \App\Models\Doctor::where('user_id', $request->user()->id)->first();
                if (!$doctor || $examination->queue->polyclinic_id !== $doctor->polyclinic_id) {
                    return $this->errorResponse('Akses ditolak. Anda tidak diizinkan menghapus rekam medis dari poliklinik lain.', 403);
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