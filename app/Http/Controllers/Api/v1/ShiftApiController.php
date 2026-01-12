<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftApiController extends Controller
{
    /**
     * Get all shifts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Shift::query()
            ->where('is_active', true)
            ->orderBy('start_time', 'asc');

        // Optional: dengan pagination
        if ($request->has('per_page')) {
            $shifts = $query->paginate($request->per_page);

            $shifts->getCollection()->transform(function ($shift) {
                return $this->formatShift($shift);
            });

            return ApiResponse::paginate($shifts, 'Data shift berhasil diambil');
        }

        // Default: tanpa pagination
        $shifts = $query->get()->map(function ($shift) {
            return $this->formatShift($shift);
        });

        return ApiResponse::success($shifts, 'Data shift berhasil diambil');
    }

    /**
     * Get current active shift
     *
     * @return JsonResponse
     */
    public function current(): JsonResponse
    {
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');

        // Cari shift yang sedang aktif berdasarkan waktu sekarang
        $currentShift = Shift::query()
            ->where('is_active', true)
            ->where(function ($query) use ($currentTime) {
                // Shift normal (start_time < end_time)
                $query->where(function ($q) use ($currentTime) {
                    $q->whereColumn('start_time', '<', 'end_time')
                        ->where('start_time', '<=', $currentTime)
                        ->where('end_time', '>', $currentTime);
                })
                // Shift malam (start_time > end_time, melewati tengah malam)
                ->orWhere(function ($q) use ($currentTime) {
                    $q->whereColumn('start_time', '>', 'end_time')
                        ->where(function ($sq) use ($currentTime) {
                            $sq->where('start_time', '<=', $currentTime)
                                ->orWhere('end_time', '>', $currentTime);
                        });
                });
            })
            ->first();

        if (!$currentShift) {
            return ApiResponse::success([
                'shift'   => null,
                'message' => 'Tidak ada shift yang aktif saat ini',
            ], 'Tidak ada shift aktif');
        }

        return ApiResponse::success([
            'shift'        => $this->formatShift($currentShift),
            'current_time' => $now->format('H:i:s'),
        ], 'Shift saat ini berhasil diambil');
    }

    /**
     * Get shift detail
     *
     * @param Shift $shift
     * @return JsonResponse
     */
    public function show(Shift $shift): JsonResponse
    {
        if (!$shift->is_active) {
            return ApiResponse::notFound('Shift tidak ditemukan');
        }

        return ApiResponse::success($this->formatShift($shift), 'Detail shift berhasil diambil');
    }

    /**
     * Format shift data
     *
     * @param Shift $shift
     * @return array
     */
    private function formatShift(Shift $shift): array
    {
        $startTime = Carbon::parse($shift->start_time);
        $endTime = Carbon::parse($shift->end_time);

        // Handle overnight shift
        if ($endTime->lessThanOrEqualTo($startTime)) {
            $endTime->addDay();
        }

        $durationHours = $startTime->diffInHours($endTime);
        $durationMinutes = $startTime->diffInMinutes($endTime) % 60;

        $duration = $durationHours . ' jam';
        if ($durationMinutes > 0) {
            $duration .= ' ' . $durationMinutes . ' menit';
        }

        $isOvernight = Carbon::parse($shift->end_time)->lessThanOrEqualTo(Carbon::parse($shift->start_time));

        // Check if current time is within this shift
        $now = Carbon::now();
        $currentTime = $now->format('H:i:s');
        $isCurrent = false;

        if ($isOvernight) {
            $isCurrent = $currentTime >= $shift->start_time || $currentTime < $shift->end_time;
        } else {
            $isCurrent = $currentTime >= $shift->start_time && $currentTime < $shift->end_time;
        }

        return [
            'id'           => $shift->id,
            'name'         => $shift->name,
            'start_time'   => $shift->start_time,
            'end_time'     => $shift->end_time,
            'duration'     => $duration,
            'is_overnight' => $isOvernight,
            'is_current'   => $isCurrent,
            'is_active'    => $shift->is_active,
            'created_at'   => $shift->created_at,
            'updated_at'   => $shift->updated_at,
        ];
    }
}
