<?php

declare(strict_types=1);

namespace Wame\LaravelTelescopeDashboard\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class AuthorizeDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        $gate = config('wame-telescope-dashboard.gate', 'viewTelescope');

        if (Gate::has($gate) && ! Gate::check($gate)) {
            abort(403);
        }

        return $next($request);
    }
}
