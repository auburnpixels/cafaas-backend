<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Sentry\Laravel\Integration;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Integration::captureUnhandledException($e);
        });

        // Custom rendering for API routes
        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/v1/*')) {
                return $this->renderApiException($e, $request);
            }
        });
    }

    /**
     * @return void
     *
     * @throws Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthenticated.'], 401);
        }

        $guard = Arr::get($exception->guards(), 0);

        switch ($guard) {
            case 'admin':
                $login = 'admin.login';
                break;
            default:
                $login = 'login';
                break;
        }

        return redirect()->guest(route($login));
    }

    /**
     * Render API exceptions in a consistent format.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    protected function renderApiException(Throwable $exception, $request)
    {
        $requestId = $request->header('X-Request-Id', uniqid('req_'));

        // Handle validation exceptions
        if ($exception instanceof ValidationException) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_PARAMETER',
                    'message' => 'Validation failed',
                    'details' => $exception->errors(),
                    'request_id' => $requestId,
                ],
            ], 422);
        }

        // Handle not found exceptions
        if ($exception instanceof NotFoundHttpException || $exception instanceof ModelNotFoundException) {
            return response()->json([
                'error' => [
                    'code' => 'NOT_FOUND',
                    'message' => 'Resource not found',
                    'request_id' => $requestId,
                ],
            ], 404);
        }

        // Handle rate limiting
        if ($exception instanceof ThrottleRequestsException) {
            $retryAfter = $exception->getHeaders()['Retry-After'] ?? 60;

            return response()->json([
                'error' => [
                    'code' => 'RATE_LIMITED',
                    'message' => 'Too many requests. Please try again later.',
                    'request_id' => $requestId,
                ],
            ], 429, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $request->attributes->get('throttle_key') === 'api:authenticated'
                    ? config('api.rate_limits.authenticated', 600)
                    : config('api.rate_limits.public', 60),
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        // Handle authentication exceptions
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required',
                    'request_id' => $requestId,
                ],
            ], 401);
        }

        // Default server error
        $statusCode = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500;

        return response()->json([
            'error' => [
                'code' => 'SERVER_ERROR',
                'message' => config('app.debug') ? $exception->getMessage() : 'An unexpected error occurred',
                'request_id' => $requestId,
            ],
        ], $statusCode);
    }
}
