<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Support\Facades\Response;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
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
    public function register()
    {
        $this->renderable(function (\Exception $e) {
            if ($e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
                return redirect()->route('login');
            };
        });
        
        $this->reportable(function (Throwable $e) {
            //
        });
    }

 /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $e)
	{
		$userLevelCheck = $e instanceof \jeremykenedy\LaravelRoles\App\Exceptions\RoleDeniedException ||
			$e instanceof \jeremykenedy\LaravelRoles\App\Exceptions\PermissionDeniedException ||
			$e instanceof \jeremykenedy\LaravelRoles\App\Exceptions\LevelDeniedException;
		
		if ($userLevelCheck) {
			
			if ($request->expectsJson()) {
				return Response::json(array(
					'error'    =>  403,
					'message'   =>  'Unauthorized.'
				), 403);
			}
			
			abort(403);
		}
		
		return parent::render($request, $e);
	}     
}
