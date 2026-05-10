<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class Idempotency
{
    private const CACHE_PREFIX = 'idempotency';
    private const CACHE_TTL_SECONDS = 86400;

    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $key = (string) $request->header('Idempotency-Key', '');
        $required = $mode !== 'optional';

        if ($key === '') {
            return $required
                ? $this->missingHeader()
                : $next($request);
        }

        $userId = $request->user()?->getKey() ?? 'guest';
        $cacheKey = sprintf('%s:%s:%s', self::CACHE_PREFIX, $userId, $key);
        $bodyHash = hash('sha256', $request->getContent());

        $cached = Cache::get($cacheKey);

        if ($cached !== null) {
            if (($cached['body_hash'] ?? null) !== $bodyHash) {
                return $this->bodyConflict();
            }

            return new JsonResponse(
                json_decode($cached['body'], true),
                $cached['status'],
                $cached['headers'] ?? [],
            );
        }

        $response = $next($request);

        if ($this->shouldCache($response)) {
            Cache::put(
                $cacheKey,
                [
                    'body_hash' => $bodyHash,
                    'status' => $response->getStatusCode(),
                    'body' => $response->getContent(),
                    'headers' => ['Content-Type' => 'application/json'],
                ],
                self::CACHE_TTL_SECONDS,
            );
        }

        return $response;
    }

    private function shouldCache(Response $response): bool
    {
        $status = $response->getStatusCode();

        return $status >= 200 && $status < 300;
    }

    private function missingHeader(): JsonResponse
    {
        return new JsonResponse([
            'error' => 'idempotency_key_required',
            'message' => 'This endpoint requires an Idempotency-Key header.',
        ], 400);
    }

    private function bodyConflict(): JsonResponse
    {
        return new JsonResponse([
            'error' => 'idempotency_key_reused_with_different_payload',
            'message' => 'The Idempotency-Key has been used with a different payload.',
        ], 409);
    }
}
