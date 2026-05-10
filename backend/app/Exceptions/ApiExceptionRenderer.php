<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenBlacklistedException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

class ApiExceptionRenderer
{
    public function __invoke(Throwable $e, Request $request): ?JsonResponse
    {
        if (! str_starts_with($request->path(), 'api/') && ! $request->expectsJson()) {
            return null;
        }

        if ($e instanceof UnauthorizedHttpException) {
            return $this->unauthorized($e);
        }

        return match (true) {
            $e instanceof ValidationException => $this->validation($e),
            $e instanceof TokenBlacklistedException => $this->envelope(401, 'token_blacklisted', 'Token has been revoked.'),
            $e instanceof TokenExpiredException => $this->envelope(401, 'token_expired', 'Access token has expired.'),
            $e instanceof TokenInvalidException => $this->envelope(401, 'token_invalid', 'Token signature is invalid.'),
            $e instanceof JWTException => $this->envelope(401, 'token_missing', 'Authorization token not provided.'),
            $e instanceof AuthenticationException => $this->envelope(401, 'token_missing', 'Authentication required.'),
            $e instanceof AuthorizationException => $this->envelope(403, 'forbidden', $e->getMessage() ?: 'You are not authorized to perform this action.'),
            $e instanceof ModelNotFoundException => $this->envelope(404, 'not_found', 'Resource not found.'),
            $e instanceof NotFoundHttpException => $this->envelope(404, 'not_found', 'Endpoint not found.'),
            $e instanceof MethodNotAllowedHttpException => $this->envelope(405, 'method_not_allowed', 'HTTP method not allowed for this endpoint.'),
            $e instanceof TooManyRequestsHttpException => $this->envelope(429, 'rate_limited', 'Too many requests. Please try again later.'),
            $e instanceof HttpExceptionInterface => $this->envelope($e->getStatusCode(), 'http_error', $e->getMessage() ?: 'Request failed.'),
            default => null,
        };
    }

    private function unauthorized(UnauthorizedHttpException $e): JsonResponse
    {
        [$error, $message] = $this->classifyUnauthorized($e);

        return $this->envelope(401, $error, $message);
    }

    private function classifyUnauthorized(UnauthorizedHttpException $e): array
    {
        $previous = $e->getPrevious();
        $message = $e->getMessage();

        switch (true) {
            case $previous instanceof TokenBlacklistedException:
            case str_contains($message, 'blacklisted'):
            case str_contains($message, 'revoked'):
                return ['token_blacklisted', 'Token has been revoked.'];

            case $previous instanceof TokenExpiredException:
            case str_contains($message, 'expired'):
                return ['token_expired', 'Access token has expired.'];

            case $previous instanceof TokenInvalidException:
                return ['token_invalid', 'Token signature is invalid.'];

            case str_contains($message, 'not provided'):
                return ['token_missing', 'Authorization token not provided.'];

            case $previous instanceof JWTException:
                return ['token_invalid', $previous->getMessage() ?: 'Token could not be parsed.'];

            default:
                return ['token_invalid', $message ?: 'Token is invalid.'];
        }
    }

    private function envelope(int $status, string $error, string $message, array $details = []): JsonResponse
    {
        $payload = ['error' => $error, 'message' => $message];

        if (! empty($details)) {
            $payload['details'] = $details;
        }

        return response()->json($payload, $status);
    }

    private function validation(ValidationException $e): JsonResponse
    {
        return $this->envelope(422, 'validation_failed', 'The given data is invalid.', $e->errors());
    }
}
