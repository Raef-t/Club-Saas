<?php

namespace Modules\Shared\Traits;

trait SuccessResponseTrait
{
    /**
     * Return a success JSON response.
     */
    public function successResponse($data, ?string $message = null, int $code = 200)
    {
        $response = [
            'success' => true,
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
    public function getPerPage(\Illuminate\Http\Request $request, int $default = 15): int
    {
        $perPage = $request->input('per_page', $default);
        if ($perPage === 'all' || $perPage === '-1' || (int)$perPage === -1) {
            return 10000;
        }
        return max(1, min((int) $perPage, 500));
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
