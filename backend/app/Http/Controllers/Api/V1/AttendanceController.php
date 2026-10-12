<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Attendance\AttendanceHistoryRequest;
use App\Http\Requests\Api\V1\Attendance\CheckInRequest;
use App\Http\Requests\Api\V1\Attendance\CheckOutRequest;
use App\Http\Resources\Api\V1\AttendanceResource;
use App\Services\AttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AttendanceController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Get current user's attendance status for today.
     */
    public function today(Request $request): JsonResponse
    {
        $summary = $this->attendanceService->getTodayAttendance($request->user());

        return response()->json([
            'data' => $summary,
            'message' => 'Success',
        ], Response::HTTP_OK);
    }

    /**
     * Process user check-in.
     */
    public function checkIn(CheckInRequest $request): JsonResponse
    {
        $record = $this->attendanceService->checkIn(
            $request->user(),
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $request->accuracy_meters,
            $request->ip()
        );

        return response()->json([
            'data' => new AttendanceResource($record),
            'message' => 'Check-in berhasil',
        ], Response::HTTP_CREATED);
    }

    /**
     * Process user check-out.
     */
    public function checkOut(CheckOutRequest $request): JsonResponse
    {
        $record = $this->attendanceService->checkOut(
            $request->user(),
            (float) $request->latitude,
            (float) $request->longitude,
            (float) $request->accuracy_meters,
            $request->ip()
        );

        return response()->json([
            'data' => new AttendanceResource($record),
            'message' => 'Check-out berhasil',
        ], Response::HTTP_OK);
    }

    /**
     * Retrieve authenticated user's attendance history.
     */
    public function history(AttendanceHistoryRequest $request): JsonResponse
    {
        $perPage = (int) ($request->per_page ?? 15);
        $paginator = $this->attendanceService->getHistory(
            $request->user(),
            $request->from,
            $request->to,
            $perPage
        );

        return response()->json([
            'data' => AttendanceResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'message' => 'Success',
        ], Response::HTTP_OK);
    }
}
