<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;

class LogRoute
{
    public function handle($request, Closure $next)
    {
        Log::info('Requête reçue', [
            'uri' => $request->getUri(),
            'method' => $request->getMethod(),
            'user' => $request->user() ? $request->user()->id : 'Non authentifié',
            'roles' => $request->user() ? $request->user()->getRoleNames() : 'Aucun'
        ]);

        return $next($request);
    }
}
