<?php

namespace ElgiborSolution\Authentication\Traits;

trait ApiResponse
{
    /**
     * Return a success JSON response.
     *
     * @param  string|null  $message
     * @param  mixed  $data
     * @param  int  $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successResponse($message = null, $data = null, $code = 200)
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Return an error JSON response.
     *
     * @param  mixed  $data
     * @param  int  $code
     * @param  string|null  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function errorResponse($data, $code = 422, $message = null)
    {
        return response()->json([
            'status' => $code,
            'message' => $message,
            'errors' => $data,
            'data' => null,
        ], $code);
    }
}
