<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransformInput
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $transformer, $error = null)
    {
        $transformedInput = [];
        foreach ($request->request->all() as $input => $value) {
            $transformedInput[$transformer::originalAttribute($input)] = $value;
        }
        $request->replace($transformedInput);
        
        // return $next($request);
        $response = $next($request);

        if(isset($response->exception) && $response->exception instanceof ValidationException)
        {
            $data = $response->getData();
            
            $transformedErrors = [];
            // $tmp = $data->message;
            
            foreach ($data->message as $field => $message) {
               
                $transformedField = $transformer::transformedAttribute($field);
                $transformedErrors[$transformedField] = str_replace($field, $transformedField, $message);
            }
            $data->message = $transformedErrors;
            $response->setData($data);
        }
        return $response;
    }
}
