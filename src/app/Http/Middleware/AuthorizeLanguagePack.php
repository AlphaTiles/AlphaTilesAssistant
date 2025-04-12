<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Collaborator;

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
            
            // Check if user is the owner
            if ($languagePack->user_id === $user->id) {
                return $next($request);
            }

            // Check if user is a collaborator
            $isCollaborator = Collaborator::where('languagepack_id', $languagePack->id)
                ->where('user_id', $user->id)
                ->exists();

            if ($isCollaborator) {
                // For collaborators, only allow view and update actions
                $route = $request->route()->getName();
                if (str_contains($route, 'delete')) {
                    abort(403, 'Collaborators cannot delete language packs');
                }
                return $next($request);
            }

            abort(403, 'Unauthorized');
        }       
        
        return $next($request);
    }
}
