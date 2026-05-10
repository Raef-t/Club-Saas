<?php

namespace Modules\Core\app\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Success Response
     */
    protected function successResponse($data, $message = null, $code = 200): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data
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
