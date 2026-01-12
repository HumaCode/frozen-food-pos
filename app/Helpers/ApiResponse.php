<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ApiResponse
{
    /**
     * Success Response
     *
     * @param mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public static function success(mixed $data = null, string $message = 'Berhasil', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Created Response (201)
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function created(mixed $data = null, string $message = 'Data berhasil dibuat'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Updated Response
     *
     * @param mixed $data
     * @param string $message
     * @return JsonResponse
     */
    public static function updated(mixed $data = null, string $message = 'Data berhasil diperbarui'): JsonResponse
    {
        return self::success($data, $message, 200);
    }

    /**
     * Deleted Response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function deleted(string $message = 'Data berhasil dihapus'): JsonResponse
    {
        return self::success(null, $message, 200);
    }

    /**
     * Paginate Response
     *
     * @param LengthAwarePaginator $paginator
     * @param string $message
     * @param JsonResource|null $resourceClass
     * @return JsonResponse
     */
    public static function paginate(
        LengthAwarePaginator $paginator,
        string $message = 'Data berhasil diambil',
        ?string $resourceClass = null
    ): JsonResponse {
        $data = $resourceClass
            ? $resourceClass::collection($paginator->items())
            : $paginator->items();

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ], 200);
    }

    /**
     * Collection Response (tanpa pagination)
     *
     * @param mixed $data
     * @param string $message
     * @param string|null $resourceClass
     * @return JsonResponse
     */
    public static function collection(
        mixed $data,
        string $message = 'Data berhasil diambil',
        ?string $resourceClass = null
    ): JsonResponse {
        $responseData = $resourceClass
            ? $resourceClass::collection($data)
            : $data;

        return self::success($responseData, $message);
    }

    /**
     * Error Response
     *
     * @param string $message
     * @param int $code
     * @param mixed $errors
     * @return JsonResponse
     */
    public static function error(string $message = 'Terjadi kesalahan', int $code = 500, mixed $errors = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Validation Error Response (422)
     *
     * @param mixed $errors
     * @param string $message
     * @return JsonResponse
     */
    public static function validationError(mixed $errors, string $message = 'Validasi gagal'): JsonResponse
    {
        return self::error($message, 422, $errors);
    }

    /**
     * Unauthorized Response (401)
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Forbidden Response (403)
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Not Found Response (404)
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function notFound(string $message = 'Data tidak ditemukan'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Bad Request Response (400)
     *
     * @param string $message
     * @param mixed $errors
     * @return JsonResponse
     */
    public static function badRequest(string $message = 'Bad Request', mixed $errors = null): JsonResponse
    {
        return self::error($message, 400, $errors);
    }

    /**
     * Too Many Requests Response (429)
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function tooManyRequests(string $message = 'Terlalu banyak permintaan'): JsonResponse
    {
        return self::error($message, 429);
    }

    /**
     * Server Error Response (500)
     *
     * @param string $message
     * @param mixed $errors
     * @return JsonResponse
     */
    public static function serverError(string $message = 'Terjadi kesalahan pada server', mixed $errors = null): JsonResponse
    {
        return self::error($message, 500, $errors);
    }

    /**
     * No Content Response (204)
     *
     * @return JsonResponse
     */
    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
