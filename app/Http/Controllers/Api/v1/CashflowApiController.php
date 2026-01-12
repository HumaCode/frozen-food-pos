<?php

namespace App\Http\Controllers\Api\v1;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CashFlow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CashflowApiController extends Controller
{
    /**
     * Get all cash flows
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = CashFlow::query()
            ->with(['user', 'shift'])
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by shift
        if ($request->has('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        // Filter by date
        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$request->start_date, $request->end_date . ' 23:59:59']);
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $cashFlows = $query->paginate($perPage);

        $cashFlows->getCollection()->transform(function ($cashFlow) {
            return $this->formatCashFlow($cashFlow);
        });

        return ApiResponse::paginate($cashFlows, 'Data kas berhasil diambil');
    }

    /**
     * Store new cash flow
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'type'        => 'required|in:in,out',
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'shift_id'    => 'nullable|exists:shifts,id',
        ], [
            'type.required'        => 'Jenis kas wajib diisi',
            'type.in'              => 'Jenis kas harus in atau out',
            'amount.required'      => 'Jumlah wajib diisi',
            'amount.min'           => 'Jumlah minimal 1',
            'description.required' => 'Keterangan wajib diisi',
            'description.max'      => 'Keterangan maksimal 500 karakter',
            'shift_id.exists'      => 'Shift tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $cashFlow = CashFlow::create([
            'type'        => $request->type,
            'amount'      => $request->amount,
            'description' => $request->description,
            'user_id'     => auth()->id(),
            'shift_id'    => $request->shift_id,
        ]);

        $cashFlow->load(['user', 'shift']);

        return ApiResponse::created($this->formatCashFlow($cashFlow), 'Kas berhasil ditambahkan');
    }

    /**
     * Get today's cash flows
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $query = CashFlow::query()
            ->with(['user', 'shift'])
            ->whereDate('created_at', today())
            ->orderBy('created_at', 'desc');

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by current user only
        if ($request->has('own') && $request->own == true) {
            $query->where('user_id', auth()->id());
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $cashFlows = $query->paginate($perPage);

        $cashFlows->getCollection()->transform(function ($cashFlow) {
            return $this->formatCashFlow($cashFlow);
        });

        return ApiResponse::paginate($cashFlows, 'Kas hari ini berhasil diambil');
    }

    /**
     * Get cash flow summary
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function summary(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        $userId = $request->get('user_id');
        $shiftId = $request->get('shift_id');

        $baseQuery = CashFlow::query()->whereDate('created_at', $date);

        if ($userId) {
            $baseQuery->where('user_id', $userId);
        }

        if ($shiftId) {
            $baseQuery->where('shift_id', $shiftId);
        }

        // Today summary
        $todayIn = (clone $baseQuery)->where('type', 'in')->sum('amount');
        $todayOut = (clone $baseQuery)->where('type', 'out')->sum('amount');
        $todayBalance = $todayIn - $todayOut;
        $todayCount = (clone $baseQuery)->count();

        // Month summary
        $monthQuery = CashFlow::query()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);

        if ($userId) {
            $monthQuery->where('user_id', $userId);
        }

        if ($shiftId) {
            $monthQuery->where('shift_id', $shiftId);
        }

        $monthIn = (clone $monthQuery)->where('type', 'in')->sum('amount');
        $monthOut = (clone $monthQuery)->where('type', 'out')->sum('amount');
        $monthBalance = $monthIn - $monthOut;

        // Recent cash flows
        $recentCashFlows = CashFlow::query()
            ->with(['user'])
            ->whereDate('created_at', $date)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($shiftId, fn($q) => $q->where('shift_id', $shiftId))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($cashFlow) {
                return [
                    'id'          => $cashFlow->id,
                    'type'        => $cashFlow->type,
                    'type_label'  => $cashFlow->type === 'in' ? 'Kas Masuk' : 'Kas Keluar',
                    'amount'      => $cashFlow->amount,
                    'description' => $cashFlow->description,
                    'user'        => $cashFlow->user?->name,
                    'created_at'  => $cashFlow->created_at,
                ];
            });

        return ApiResponse::success([
            'date' => $date,
            'today' => [
                'total_in'     => $todayIn,
                'total_out'    => $todayOut,
                'balance'      => $todayBalance,
                'transactions' => $todayCount,
            ],
            'month' => [
                'total_in'  => $monthIn,
                'total_out' => $monthOut,
                'balance'   => $monthBalance,
            ],
            'recent_cash_flows' => $recentCashFlows,
        ], 'Ringkasan kas berhasil diambil');
    }

    /**
     * Get cash flow detail
     *
     * @param CashFlow $cashFlow
     * @return JsonResponse
     */
    public function show(CashFlow $cashFlow): JsonResponse
    {
        $cashFlow->load(['user', 'shift']);

        return ApiResponse::success($this->formatCashFlow($cashFlow), 'Detail kas berhasil diambil');
    }

    /**
     * Update cash flow
     *
     * @param Request $request
     * @param CashFlow $cashFlow
     * @return JsonResponse
     */
    public function update(Request $request, CashFlow $cashFlow): JsonResponse
    {
        // Hanya user yang membuat yang bisa update
        if ($cashFlow->user_id !== auth()->id()) {
            return ApiResponse::forbidden('Anda tidak memiliki akses untuk mengubah data ini');
        }

        $validator = Validator::make($request->all(), [
            'type'        => 'required|in:in,out',
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string|max:500',
            'shift_id'    => 'nullable|exists:shifts,id',
        ], [
            'type.required'        => 'Jenis kas wajib diisi',
            'type.in'              => 'Jenis kas harus in atau out',
            'amount.required'      => 'Jumlah wajib diisi',
            'amount.min'           => 'Jumlah minimal 1',
            'description.required' => 'Keterangan wajib diisi',
            'description.max'      => 'Keterangan maksimal 500 karakter',
            'shift_id.exists'      => 'Shift tidak ditemukan',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError($validator->errors());
        }

        $cashFlow->update([
            'type'        => $request->type,
            'amount'      => $request->amount,
            'description' => $request->description,
            'shift_id'    => $request->shift_id,
        ]);

        $cashFlow->load(['user', 'shift']);

        return ApiResponse::updated($this->formatCashFlow($cashFlow), 'Kas berhasil diperbarui');
    }

    /**
     * Delete cash flow
     *
     * @param CashFlow $cashFlow
     * @return JsonResponse
     */
    public function destroy(CashFlow $cashFlow): JsonResponse
    {
        // Hanya user yang membuat yang bisa hapus
        if ($cashFlow->user_id !== auth()->id()) {
            return ApiResponse::forbidden('Anda tidak memiliki akses untuk menghapus data ini');
        }

        $cashFlow->delete();

        return ApiResponse::deleted('Kas berhasil dihapus');
    }

    /**
     * Format cash flow data
     *
     * @param CashFlow $cashFlow
     * @return array
     */
    private function formatCashFlow(CashFlow $cashFlow): array
    {
        return [
            'id'          => $cashFlow->id,
            'type'        => $cashFlow->type,
            'type_label'  => $cashFlow->type === 'in' ? 'Kas Masuk' : 'Kas Keluar',
            'amount'      => $cashFlow->amount,
            'description' => $cashFlow->description,
            'user_id'     => $cashFlow->user_id,
            'user'        => $cashFlow->user ? [
                'id'   => $cashFlow->user->id,
                'name' => $cashFlow->user->name,
            ] : null,
            'shift_id'    => $cashFlow->shift_id,
            'shift'       => $cashFlow->shift ? [
                'id'   => $cashFlow->shift->id,
                'name' => $cashFlow->shift->name,
            ] : null,
            'created_at'  => $cashFlow->created_at,
            'updated_at'  => $cashFlow->updated_at,
        ];
    }
}
