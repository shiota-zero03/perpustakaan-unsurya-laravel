<?php

namespace App\Resources\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public function successResponse(string $message, array $data = []): JsonResponse
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ]);
    }

    public function errorResponse(string $message, array $error = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $error
        ], $code);
    }
}
