<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Medicine;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Exception;

class MedicineController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        $query = Medicine::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('page') || $request->has('per_page')) {
            $limit = $request->input('per_page', 20);
            return $this->successResponse($query->paginate($limit), 'Daftar obat berhasil diambil');
        }

        return $this->successResponse($query->get(), 'Daftar obat berhasil diambil');
    }

    public function show($id)
    {
        try {
            $medicine = Medicine::findOrFail($id);
            return $this->successResponse($medicine, 'Detail obat ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data obat tidak ditemukan', 404);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:medicines,name',
            'stock' => 'required|integer|min:0',
            'unit' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        try {
            $medicine = Medicine::create($validated);
            return $this->successResponse($medicine, 'Obat baru berhasil ditambahkan', 201);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menambahkan obat: ' . $e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|unique:medicines,name,' . $id,
            'stock' => 'sometimes|required|integer|min:0',
            'unit' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        try {
            $medicine = Medicine::findOrFail($id);
            $medicine->update($validated);
            return $this->successResponse($medicine, 'Data obat berhasil diperbarui');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui data obat: ' . $e->getMessage(), 500);
        }
    }

    public function destroy($id)
    {
        try {
            $medicine = Medicine::findOrFail($id);
            $medicine->delete();
            return $this->successResponse(null, 'Obat berhasil dihapus (soft delete)');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus obat: ' . $e->getMessage(), 500);
        }
    }

    public function restore($id)
    {
        try {
            $medicine = Medicine::onlyTrashed()->findOrFail($id);
            $medicine->restore();
            return $this->successResponse($medicine, 'Obat berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan obat', 404);
        }
    }
}
