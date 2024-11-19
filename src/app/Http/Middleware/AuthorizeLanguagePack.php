<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthorizeLanguagePack
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $languagePack = $request->route('languagePack'); 
        
        if ($languagePack && !session('masterpw')) {
            $user = $request->user();
            if ($user->can('view', $languagePack) ||
                $user->can('update', $languagePack) ||
                $user->can('delete', $languagePack)) {
                return $next($request);
            }

            abort(403, 'Unauthorized');
        }       
        
        return $next($request);
    }
}
