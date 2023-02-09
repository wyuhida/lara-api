<?php

namespace App\Exceptions;

use App\Helper\ApiResponser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Mockery\Exception\InvalidOrderException;
use PHPUnit\Framework\MockObject\InvalidMethodNameException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ApiResponser;
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        //
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    // public function register()
    // {
    //     // $this->reportable(function (Throwable $e) {
    //     //     //
    //     // });

    //     $this->renderable(function (NotFoundHttpException $e, $request) {
    //         if($request->is('api/*'))
    //         {
    //             return $this->errorResponse("Dose not exists with the specified identificator", 404);
    //         }
    //     });

    // }

    public function render($request, Throwable $exception)
    {
        if($exception instanceof ValidationException){
            return $this->convertValidationExceptionToResponse($exception, $request);
        }

        if($exception instanceof AuthenticationException){
           return $this->unauthenticated($request, $exception);
       }
       
       if($exception instanceof AuthorizationException){
            return $this->errorResponse($exception->getMessage(), 403);
        }        
        if($exception instanceof MethodNotAllowedHttpException){
            return $this->errorResponse('The specified method is invalid', 405);
        }

        if($exception instanceof NotFoundHttpException)
        {
            return $this->errorResponse('The specified URL cannot be found', 404);
        }
        if($exception instanceof ModelNotFoundException)
        {
            $modelName = strtolower(class_basename($exception->getModel()));
            return $this->errorResponse("Does not exists any {$modelName} with the specified identificator", 404);
        }

        if($exception instanceof HttpException)
        {
            return $this->errorResponse($exception->getMessage(), $exception->getStatusCode());
        }

        if($exception instanceof QueryException)
        {
            // dd($exception);
            $errorCode = $exception->errorInfo[1];
            if($errorCode == 1451)
            {
                return $this->errorResponse('Cannot remove this resource permantly. It is related with any other resource', 409);
            }
        }

        if($exception instanceof TokenMismatchException)
        {
            return redirect()->back()->withInput($request->input());
        }

        if(config('app.debug')){
            return parent::render($request, $exception);       
        }
        return $this->errorResponse('Unexpected Exception. try later', 500);

    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        //utk frontend
        if($this->isFrontend($request))
        {
            return redirect()->guest('login');
        }

        return $this->errorResponse('Unauthenticated', 401);
    }

    /**
     * Create a response object from the given validation exception.
     *
     * @param  \Illuminate\Validation\ValidationException  $e
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function convertValidationExceptionToResponse(ValidationException $e, $request)
    {
        $errors = $e->validator->errors()->getMessages();

        //utk frontend
        if($this->isFrontend($request))
        {
            return $request->ajax() ? response()->json($errors, 422) : redirect()
                ->back()
                ->withInput($request->input())
                ->withErrors($errors);
        }

        return $this->errorResponse($errors, 422);
    }

    private function isFrontend($request)
    {
        return $request->acceptsHtml() && collect($request->route()->middleware())->contains('web');
    }
    
    
}
