<?php

namespace App\Middleware;

use App\Core\Middleware;

/**
 * CORS middleware untuk route group API.
 *
 * SECURITY (CWE-942): implementasi lama melakukan refleksi wildcard '*'
 * yang membuat API terbaca lintas origin mana pun. Kini mendelegasikan ke
 * satu-satunya implementasi hardened di Core\Middleware (origin eksplisit
 * saja + Vary: Origin) supaya hanya ada satu kode CORS di aplikasi.
 */
class CorsMiddleware implements MiddlewareInterface
{
    public function handle($request, \Closure $next)
    {
        Middleware::cors();

        return $next($request);
    }
}
