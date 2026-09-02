<?php

namespace Modules\Core\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

trait ApiResponse
{
    /**
     * Success Response
     */
    protected function successResponse($data, $message = null, int $code = 200): JsonResponse
    {
        // 1. If $data is a ResourceCollection wrapping a LengthAwarePaginator
        if ($data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $paginator = $data->resource;
            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data->resolve(),
                'meta'    => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ]
            ], $code);
        }

        // 2. If $data is directly a LengthAwarePaginator
        if ($data instanceof LengthAwarePaginator) {
            return response()->json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data->items(),
                'meta'    => [
                    'current_page' => $data->currentPage(),
                    'last_page'    => $data->lastPage(),
                    'per_page'     => $data->perPage(),
                    'total'        => $data->total(),
                    'from'         => $data->firstItem(),
                    'to'           => $data->lastItem(),
                ]
            ], $code);
        }

        // 3. If $data is an array that already has pagination format from Laravel Resource:
        // e.g. Resource::collection($paginator)->response()->getData(true)
        if (is_array($data) && isset($data['data']) && isset($data['meta'])) {
            $meta = $data['meta'];
            $responseData = [
                'status'  => 'success',
                'message' => $message,
                'data'    => $data['data'],
                'meta'    => [
                    'current_page' => $meta['current_page'] ?? null,
                    'last_page'    => $meta['last_page'] ?? null,
                    'per_page'     => $meta['per_page'] ?? null,
                    'total'        => $meta['total'] ?? null,
                    'from'         => $meta['from'] ?? null,
                    'to'           => $meta['to'] ?? null,
                ]
            ];
            if (isset($data['links'])) {
                $responseData['links'] = $data['links'];
            }
            if (isset($data['totals'])) {
                $responseData['totals'] = $data['totals'];
            }
            return response()->json($responseData, $code);
        }

        return response()->json([
            'status'  => 'success',
            'message' => $message,
            'data'    => $data
        ], $code);
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
