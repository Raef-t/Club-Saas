<?php

namespace Modules\Shared\Traits;

trait SuccessResponseTrait
{
    /**
     * Return a success JSON response.
     */
    public function successResponse($data, ?string $message = null, int $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     */
    public function error(string $message, int $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }
}
