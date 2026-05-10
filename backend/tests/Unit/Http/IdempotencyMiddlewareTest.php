<?php

namespace Tests\Unit\Http;

use App\Http\Middleware\Idempotency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
    }

    public function test_returns_400_when_key_missing_in_required_mode(): void
    {
        $middleware = new Idempotency();
        $request = Request::create('/test', 'POST');

        $response = $middleware->handle($request, fn () => new JsonResponse(['ok' => true], 200));

        $this->assertSame(400, $response->getStatusCode());
        $this->assertSame('idempotency_key_required', json_decode($response->getContent(), true)['error']);
    }

    public function test_passes_through_when_key_missing_in_optional_mode(): void
    {
        $middleware = new Idempotency();
        $request = Request::create('/test', 'POST');
        $called = false;

        $response = $middleware->handle($request, function () use (&$called) {
            $called = true;
            return new JsonResponse(['ok' => true], 200);
        }, 'optional');

        $this->assertTrue($called);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_caches_successful_response_and_replays_on_repeat(): void
    {
        $middleware = new Idempotency();
        $key = 'test-key-1';
        $callCount = 0;
        $next = function () use (&$callCount) {
            $callCount++;
            return new JsonResponse(['result' => "call-{$callCount}"], 200);
        };

        $first = $middleware->handle($this->requestWithKey($key, '{"foo":"bar"}'), $next);
        $second = $middleware->handle($this->requestWithKey($key, '{"foo":"bar"}'), $next);

        $this->assertSame(1, $callCount);
        $this->assertSame(200, $second->getStatusCode());
        $this->assertSame($first->getContent(), $second->getContent());
    }

    public function test_returns_409_when_same_key_used_with_different_body(): void
    {
        $middleware = new Idempotency();
        $key = 'test-key-2';

        $middleware->handle(
            $this->requestWithKey($key, '{"foo":"bar"}'),
            fn () => new JsonResponse(['ok' => true], 200),
        );

        $response = $middleware->handle(
            $this->requestWithKey($key, '{"foo":"different"}'),
            fn () => new JsonResponse(['ok' => true], 200),
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            'idempotency_key_reused_with_different_payload',
            json_decode($response->getContent(), true)['error'],
        );
    }

    public function test_does_not_cache_non_2xx_responses(): void
    {
        $middleware = new Idempotency();
        $key = 'test-key-3';
        $callCount = 0;
        $next = function () use (&$callCount) {
            $callCount++;
            return new JsonResponse(['error' => 'boom'], 500);
        };

        $first = $middleware->handle($this->requestWithKey($key, '{"foo":"bar"}'), $next);
        $second = $middleware->handle($this->requestWithKey($key, '{"foo":"bar"}'), $next);

        $this->assertSame(2, $callCount);
        $this->assertSame(500, $first->getStatusCode());
        $this->assertSame(500, $second->getStatusCode());
    }

    public function test_cache_is_scoped_per_user(): void
    {
        $middleware = new Idempotency();
        $key = 'test-key-4';
        $callCount = 0;
        $next = function () use (&$callCount) {
            $callCount++;
            return new JsonResponse(['call' => $callCount], 200);
        };

        $userA = $this->userStub(1);
        $userB = $this->userStub(2);

        $middleware->handle($this->requestWithKey($key, '{"x":1}', $userA), $next);
        $middleware->handle($this->requestWithKey($key, '{"x":1}', $userB), $next);

        $this->assertSame(2, $callCount);
    }

    private function requestWithKey(string $key, string $body, ?object $user = null): Request
    {
        $request = Request::create('/test', 'POST', content: $body);
        $request->headers->set('Idempotency-Key', $key);
        $request->headers->set('Content-Type', 'application/json');

        if ($user !== null) {
            $request->setUserResolver(fn () => $user);
        }

        return $request;
    }

    private function userStub(int $id): object
    {
        return new class($id) {
            public function __construct(private readonly int $id) {}

            public function getKey(): int
            {
                return $this->id;
            }
        };
    }
}
