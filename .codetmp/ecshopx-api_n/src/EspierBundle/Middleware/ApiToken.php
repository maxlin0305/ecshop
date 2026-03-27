<?php

namespace EspierBundle\Middleware;

use Closure;
use Exception;

class ApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $sign = $request->input('token');
        if (!$sign || $sign != config('common.api_token')) {
            throw new Exception('无权访问该API,签名错误');
        }
        return $next($request);
    }
}
