<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                if (!method_exists($user, 'homeUrl')) {
                    return redirect(RouteServiceProvider::HOME);
                }

                /*
                 * Mismo criterio que al iniciar sesión: si compró y todavía no
                 * eligió la dirección de su invitación, ese paso va antes que el
                 * panel. Por aquí pasa quien vuelve a /login con la sesión ya
                 * abierta, que es lo que ocurre al volver de pagar.
                 */
                return redirect(
                    $this->authService->profileSetupUrlIfPending($user) ?? $user->homeUrl()
                );
            }
        }

        return $next($request);
    }
}
