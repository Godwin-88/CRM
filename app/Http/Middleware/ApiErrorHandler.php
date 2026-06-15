<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiErrorHandler
{
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     */
    public function handle($request, Closure $next): mixed
    {
        $response = $next($request);

        $this->addRequestIdHeader($response, $request);

        return $response;
    }

    protected function addRequestIdHeader($response, $request): void
    {
        if ($response instanceof JsonResponse) {
            $requestId = $request->attributes->get('request_id', (string) str()->ulid());
            $response->headers->set('X-Request-ID', $requestId);
        }
    }

    public function render($request, Throwable $e): JsonResponse
    {
        $statusCode = $this->getStatusCode($e);
        $error = $this->formatError($e, $statusCode);

        return response()->json([
            'error' => $error,
        ], $statusCode);
    }

    protected function getStatusCode(Throwable $e): int
    {
        return match (true) {
            $e instanceof ValidationException => 422,
            $e instanceof AuthorizationException => 403,
            $e instanceof AuthenticationException => 401,
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => 404,
            $e instanceof MethodNotAllowedHttpException => 405,
            $e instanceof ThrottleRequestsException => 429,
            $e instanceof HttpException => $e->getStatusCode(),
            default => method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500,
        };
    }

    protected function formatError(Throwable $e, int $statusCode): array
    {
        $code = match ($statusCode) {
            422 => 'validation_error',
            403 => 'forbidden',
            401 => 'unauthenticated',
            404 => 'not_found',
            405 => 'method_not_allowed',
            429 => 'rate_limit_exceeded',
            500 => 'server_error',
            default => 'error',
        };

        $message = $statusCode === 422 && $e instanceof ValidationException
            ? 'The given data was invalid.'
            : $e->getMessage();

        $details = [];

        if ($e instanceof ValidationException && $e->errors()) {
            $details['validation'] = $e->errors();
        }

        return [
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ];
    }
}
