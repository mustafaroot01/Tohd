<?php

namespace App\Http\Middleware;

use App\Services\ProfileCompletionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes a switched-off feature's URIs indistinguishable from URIs that were
 * never registered.
 *
 * Route middleware is not enough for that: the router answers OPTIONS with
 * 200 + an `Allow` header, and a wrong verb with 405, before any route
 * middleware runs — and authentication answers 401 before the feature gate
 * gets a turn. All three tell an anonymous prober that the endpoint exists.
 * This runs in the global stack, ahead of routing, so the answer is the same
 * 404 envelope any unregistered path gets, on every verb, with or without a
 * token.
 */
class HideDisabledFeatureRoutes
{
    /** URIs that must not exist at all while the completion step is switched off. */
    private const PROFILE_COMPLETION = [
        'api/v1/app/governorates',
        'api/v1/app/profile/details',
    ];

    public function __construct(private readonly ProfileCompletionService $completion) {}

    public function handle(Request $request, Closure $next): Response
    {
        // the URI is tested first, so the database is touched for these two paths only
        if ($request->is(...self::PROFILE_COMPLETION) && ! $this->completion->isEnabled()) {
            abort(Response::HTTP_NOT_FOUND);
        }

        return $next($request);
    }
}
