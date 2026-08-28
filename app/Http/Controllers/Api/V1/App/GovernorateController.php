<?php

namespace App\Http\Controllers\Api\V1\App;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class GovernorateController extends Controller
{
    /** The list the completion form offers — active ones, in order. */
    public function index(): JsonResponse
    {
        return ApiResponse::success(
            data: Governorate::active()
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name'])
                ->all(),
            message: 'تم استرجاع قائمة المحافظات بنجاح'
        );
    }
}
