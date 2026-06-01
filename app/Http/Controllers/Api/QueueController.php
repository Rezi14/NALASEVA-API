<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QueueService;
use App\Traits\ApiResponse;
use App\Http\Requests\StoreQueueRequest;
use App\Http\Requests\UpdateQueueRequest;
use App\Http\Resources\QueueResource;
use Illuminate\Http\Request;
use Exception;

class QueueController extends Controller
{
    use ApiResponse;

    protected QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function index(Request $request)
    {
        $query = $this->queueService->getQueuesQuery($request->user());

        if ($request->has('page') || $request->has('per_page') || $request->has('paginate')) {
            $limit = $request->input('per_page', 20);
            return $this->successResponse(QueueResource::collection($query->paginate($limit)), 'Daftar antrian berhasil diambil');
        }

        return $this->successResponse(QueueResource::collection($query->get()), 'Daftar antrian berhasil diambil');
    }

    public function store(StoreQueueRequest $request)
    {
        try {
            $queue = $this->queueService->storeQueue($request->user(), $request->validated());
            return $this->successResponse(new QueueResource($queue), 'Antrian berhasil dibuat', 201);
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [403, 404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            $query = $this->queueService->getQueuesQuery($request->user());
            $queue = $query->findOrFail($id);
            return $this->successResponse(new QueueResource($queue), 'Detail antrian ditemukan');
        } catch (Exception $e) {
            return $this->errorResponse('Data antrian tidak ditemukan', 404);
        }
    }

    public function update(UpdateQueueRequest $request, $id)
    {
        try {
            $queue = $this->queueService->updateQueue($request->user(), $id, $request->validated());
            return $this->successResponse(new QueueResource($queue), 'Status antrian berhasil diperbarui');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [403, 404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function destroy(Request $request, $id)
    {
        try {
            $queue = $this->queueService->destroyQueue($request->user(), $id);
            return $this->successResponse(new QueueResource($queue), 'Antrian berhasil dibatalkan');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [403, 404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function restore($id)
    {
        try {
            $this->queueService->restoreQueue($id);
            return $this->successResponse(null, 'Data antrian berhasil dikembalikan');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengembalikan, data tidak ditemukan di tempat sampah', 404);
        }
    }

    public function checkIn(Request $request, $id)
    {
        try {
            $queue = $this->queueService->checkInQueue($request->user(), $id, $request->input('reason'));
            return $this->successResponse(new QueueResource($queue), 'Check-in berhasil via QR Scanner');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [400, 403, 404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function recall(Request $request, $id)
    {
        try {
            $queue = $this->queueService->recallQueue($request->user(), $id);
            return $this->successResponse(new QueueResource($queue), 'Panggilan ulang berhasil dilakukan');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [400, 403, 404, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function skip(Request $request, $id)
    {
        try {
            $queue = $this->queueService->skipQueue($request->user(), $id);
            return $this->successResponse(new QueueResource($queue), 'Antrean berhasil digeser ke urutan paling belakang');
        } catch (Exception $e) {
            $statusCode = in_array($e->getCode(), [403, 422]) ? $e->getCode() : 500;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}
