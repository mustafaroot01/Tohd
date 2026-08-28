<?php

namespace App\Http\Middleware;

use App\Services\ProfileCompletionService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * With the switch on, the second step is mandatory: training and subscription
 * endpoints refuse until the profile is filled, so a modified client cannot
 * skip the screen the app shows at launch. With the switch off this is a
 * no-op and leaves no trace in any response.
 */
class EnsureProfileIsComplete
{
    public function __construct(private readonly ProfileCompletionService $completion) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->completion->isEnabled() && ! $this->completion->isComplete($user)) {
            return ApiResponse::error(
                message: 'يرجى إكمال بياناتك أولاً للمتابعة',
                errorCode: 'PROFILE_INCOMPLETE',
                status: Response::HTTP_FORBIDDEN
            );
        }

        return $next($request);
    }
}
