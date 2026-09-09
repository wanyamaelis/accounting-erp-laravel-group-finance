<?php

namespace App\Http\Middleware;

use App\Support\Installer;
use Closure;
use Illuminate\Http\Request;

class RedirectIfNotInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $installer = app(Installer::class);

        if ($installer->installed()) {
            return $next($request);
        }

        $allowed = $request->is([
            'install*',
            'up',
            'api/*',
            'vendor/*',
            'storage/*',
            '_ignition/*',
            'horizon/*',
            'filament/*',
        ]);

        if ($allowed) {
            return $next($request);
        }

        if ($request->is('stripe/webhook')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Application is not installed'], 423);
        }

        return redirect()->to('/install');
    }
}
