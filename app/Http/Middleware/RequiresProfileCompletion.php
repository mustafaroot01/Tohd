<?php

namespace App\Http\Middleware;

use App\Services\ProfileCompletionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hides the completion feature entirely while its switch is off.
 *
 * A 404 — not a 403 — on purpose: with the switch off these endpoints must
 * look like they were never built, so nothing about the feature can be
 * discovered from the API.
 */
class RequiresProfileCompletion
{
    public function __construct(private readonly ProfileCompletionService $completion) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->completion->isEnabled(), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
