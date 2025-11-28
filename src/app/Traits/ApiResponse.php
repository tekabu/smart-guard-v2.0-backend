<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * Return a success response
     *
     * @param mixed $data
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function successResponse($data, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return an error response
     *
     * @param string $message
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 422): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $statusCode);
    }
}
