<?php

use App\Http\Middleware\AuthenticateToken;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(api: __DIR__.'/../routes/api.php', apiPrefix: 'api/v1', commands: __DIR__.'/../routes/console.php')
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(append: [AuthenticateToken::class]);
        $middleware->alias(['role' => RequireRole::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*')) return null;
            $status = 500; $code = 'INTERNAL_ERROR'; $message = 'An unexpected error occurred.'; $details = [];
            if ($exception instanceof ValidationException) {
                $status = 422; $code = 'VALIDATION_ERROR'; $message = 'The submitted data is invalid.';
                foreach ($exception->errors() as $field => $issues) foreach ($issues as $issue) $details[] = ['field' => $field, 'issue' => $issue];
            } elseif ($exception instanceof HttpExceptionInterface) {
                $status = $exception->getStatusCode();
                [$code, $message] = match ($status) { 401 => ['UNAUTHENTICATED', 'Authentication is required.'], 403 => ['FORBIDDEN_ROLE', 'You are not allowed to perform this action.'], 404 => ['NOT_FOUND', 'The requested resource was not found.'], default => ['HTTP_ERROR', 'The request could not be completed.'] };
            } elseif ((string) $exception->getCode() === '23505') {
                $status = 409; $code = 'RESOURCE_CONFLICT'; $message = 'A record with the same unique value already exists.';
            }
            return response()->json(['code' => $code, 'message' => $message, 'details' => $details], $status);
        });
    })->create();
