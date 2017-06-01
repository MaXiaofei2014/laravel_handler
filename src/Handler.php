<?php

namespace Ckryo\Laravel\Handler\Exceptions;

use Ckryo\Laravel\Http\Contracts\ExceptionFactory;
use Ckryo\Laravel\Http\ErrorCodeException;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
        \Ckryo\Laravel\Http\ErrorCodeException::class
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($request->isJson()) {
            switch (true) {
                /// 默认处理错误
                case $exception instanceof ExceptionFactory:
                    return $exception->handle();
                /// 表单验证错误数据处理方式
                case $exception instanceof ValidationException:
                    $errorBag = $exception->validator->errors();
                    $errors = [];
                    foreach ($errorBag->messages() as $key => $message) {
                        $errors[$key] = $message[0];
                    }
                    throw new ErrorCodeException(9, $errorBag->first(), $errors);
                    break;
                /// 路由不存在 - 404
                case $exception instanceof MethodNotAllowedHttpException:
                    throw new ErrorCodeException(21);
            }

            throw new ErrorCodeException(1, $exception->getMessage(), [
                $exception->getLine(),
                $exception->getFile(),
                $exception->getCode(),
                $exception->getPrevious(),
                $exception->getTrace(),
                $exception->getTraceAsString()
            ]);
        }
        return parent::render($request, $exception);
    }

    // 未授权用户, 需用重新登录的。
    protected function unauthenticated()
    {
        throw new ErrorCodeException(200);
    }
}