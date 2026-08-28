<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\SubscriberProfileResource;
use App\Models\UserCurriculumAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriberDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $subscriber = $this->resource['subscriber'];
        $current = $subscriber->activeCurriculumAssignment;
        $progress = $this->resource['progress'];

        return [
            'id' => $subscriber->id,
            'name' => $subscriber->name,
            'phone' => $subscriber->phone,
            'address' => $subscriber->address,
            'status' => $subscriber->status?->value,
            'status_label' => $subscriber->status?->label(),
            'phone_verified_at' => $subscriber->phone_verified_at?->toISOString(),
            'last_login_at' => $subscriber->last_login_at?->toISOString(),
            'last_activity_at' => $subscriber->last_activity_at?->toISOString(),
            'created_at' => $subscriber->created_at?->toISOString(),
            'updated_at' => $subscriber->updated_at?->toISOString(),

            'current_subscription' => $current ? $this->formatAssignment($current) : null,

            'subscription_history' => $subscriber->curriculumAssignments
                ->map(fn (UserCurriculumAssignment $assignment) => $this->formatAssignment($assignment))
                ->values(),

            'serial_history' => $subscriber->activations->map(fn ($activation) => [
                'id' => $activation->id,
                'code' => $activation->code,
                'status' => $activation->status?->value,
                'product' => $activation->product ? [
                    'id' => $activation->product->id,
                    'name' => $activation->product->name,
                ] : null,
                'curriculum' => $activation->product?->curriculum ? [
                    'id' => $activation->product->curriculum->id,
                    'name' => $activation->product->curriculum->name,
                ] : null,
                'activated_at' => $activation->activated_at?->toISOString(),
                'expires_at' => $activation->expires_at?->toISOString(),
            ])->values(),

            'courses' => $subscriber->curriculumAssignments
                ->pluck('curriculum')
                ->filter()
                ->unique('id')
                ->map(fn ($curriculum) => [
                    'id' => $curriculum->id,
                    'name' => $curriculum->name,
                    'code' => $curriculum->code,
                ])->values(),

            // all-time, from the board: bounded by games, not by play
            'play_summary' => [
                'attempts' => $progress['total_attempts'],
                'short_attempts' => $progress['grades']['short_attempts'],
                'games_played' => $progress['games_played'],
                'games_passed' => $progress['games_passed'],
                'skipped_games' => $progress['games_skipped'],
            ],

            // the supplementary details, when they were ever filled in
            'details' => $subscriber->profile
                ? (new SubscriberProfileResource($subscriber->profile))->resolve()
                : null,

            'progress' => $progress,

            // the current week, game by game; other weeks via /weekly?week=
            'weekly' => $this->resource['weekly'],

            'activity_timeline' => $subscriber->activities->map(fn ($activity) => [
                'type' => $activity->type?->value,
                'label' => $activity->type?->label(),
                'metadata' => $activity->metadata,
                'created_at' => $activity->created_at?->toISOString(),
            ])->values(),
        ];
    }

    protected function formatAssignment(UserCurriculumAssignment $assignment): array
    {
        return [
            'id' => $assignment->id,
            'curriculum' => $assignment->curriculum ? [
                'id' => $assignment->curriculum->id,
                'name' => $assignment->curriculum->name,
                'code' => $assignment->curriculum->code,
            ] : null,
            'product' => $assignment->activation?->product ? [
                'id' => $assignment->activation->product->id,
                'name' => $assignment->activation->product->name,
            ] : null,
            'serial' => $assignment->activation?->code,
            'activated_at' => $assignment->activation?->activated_at?->toISOString(),
            'starts_at' => $assignment->starts_at?->toISOString(),
            'ends_at' => $assignment->ends_at?->toISOString(),
            'days_remaining' => $assignment->ends_at ? max(0, (int) now()->diffInDays($assignment->ends_at, false)) : null,
            'status' => $assignment->status?->value,
            'cancelled_at' => $assignment->cancelled_at?->toISOString(),
            'cancellation_reason' => $assignment->cancellation_reason,
        ];
    }
}
