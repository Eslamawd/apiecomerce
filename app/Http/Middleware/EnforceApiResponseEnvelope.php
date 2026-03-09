<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiResponseEnvelope
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*') || ! $response instanceof JsonResponse) {
            return $response;
        }

        $payload = $response->getData(true);

        if (! is_array($payload)) {
            return $response;
        }

        if (array_key_exists('success', $payload) && array_key_exists('message', $payload) && array_key_exists('data', $payload)) {
            return $response;
        }

        $status = $response->getStatusCode();
        $isSuccess = $status >= 200 && $status < 400;

        $message = is_string($payload['message'] ?? null)
            ? $payload['message']
            : ($isSuccess ? 'Request completed successfully.' : 'Request failed.');

        $wrapped = [
            'success' => $isSuccess,
            'message' => $message,
            // Keep original payload shape inside data so frontend normalizers remain stable.
            'data' => $isSuccess ? $payload : ($payload['data'] ?? null),
        ];

        if (! $isSuccess && array_key_exists('errors', $payload)) {
            $wrapped['errors'] = $payload['errors'];
        }

        return response()->json($wrapped, $status);
    }
}
