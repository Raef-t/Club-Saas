<?php

namespace Modules\Core\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success Response
     */
    protected function successResponse($data, $message = null, int $code = 200): JsonResponse
    {
        $response = [
            'status'  => 'success',
            'message' => $message,
        ];

        if ($data instanceof \Illuminate\Http\Resources\Json\ResourceCollection && $data->resource instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $paginator = $data->resource;
            $response['data'] = $data;
            $response['meta'] = [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ];
            $response['links'] = [
                'first' => $paginator->url(1),
                'last'  => $paginator->url($paginator->lastPage()),
                'prev'  => $paginator->previousPageUrl(),
                'next'  => $paginator->nextPageUrl(),
            ];
            return response()->json($response, $code);
        }

        if ($data instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator || $data instanceof \Illuminate\Contracts\Pagination\Paginator) {
            $response['data'] = $data->items();
            $response['meta'] = [
                'current_page' => $data->currentPage(),
                'last_page'    => method_exists($data, 'lastPage') ? $data->lastPage() : null,
                'per_page'     => $data->perPage(),
                'total'        => method_exists($data, 'total') ? $data->total() : null,
            ];
            $response['links'] = [
                'first' => $data->url(1),
                'last'  => method_exists($data, 'lastPage') ? $data->url($data->lastPage()) : null,
                'prev'  => $data->previousPageUrl(),
                'next'  => $data->nextPageUrl(),
            ];
            return response()->json($response, $code);
        }

        $response['data'] = $data;
        return response()->json($response, $code);
    }

    /**
     * Get per page count from request with support for 'all'.
     */
    protected function getPerPage(\Illuminate\Http\Request $request, int $default = 15): int
    {
        $perPage = $request->input('per_page', $default);
        if ($perPage === 'all' || $perPage === '-1' || (int)$perPage === -1) {
            return 10000;
        }
        return max(1, min((int) $perPage, 500));
    }

    /**
     * Error Response
     */
    protected function errorResponse($message, $code): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'data' => null
        ], $code);
    }

    /**
     * Validation Error Response
     */
    protected function validationErrorResponse($errors): JsonResponse
    {
        return response()->json([
            'status' => 'validation_error',
            'message' => __('Validation Error'),
            'errors' => $errors
        ], 422);
    }
}
