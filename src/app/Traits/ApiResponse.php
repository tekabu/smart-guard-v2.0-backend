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
     * @param array $errors Optional validation errors array
     * @return JsonResponse
     */
    protected function errorResponse(string $message, int $statusCode = 422, array $errors = []): JsonResponse
    {
        $response = [
            'status' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a validation error response
     *
     * @param string $field
     * @param string $message
     * @return JsonResponse
     */
    protected function validationErrorResponse(string $field, string $message): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => [
                $field => [$message],
            ],
        ], 422);
    }
}
