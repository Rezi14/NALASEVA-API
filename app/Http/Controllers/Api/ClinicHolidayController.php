<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ClinicHoliday;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClinicHolidayController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $holidays = ClinicHoliday::where('holiday_date', '>=', now()->toDateString())->get();
        return $this->successResponse($holidays, 'Daftar hari libur berhasil diambil');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'holiday_date' => 'required|date|unique:clinic_holidays,holiday_date',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors()->first(), 422);
        }

        $holiday = ClinicHoliday::create($validator->validated());
        return $this->successResponse($holiday, 'Hari libur berhasil ditambahkan', 201);
    }
}
