<?php

namespace App\Services;

use App\Models\Subscriber;
use App\Models\SubscriberProfile;
use App\Models\SystemSetting;

/**
 * The second registration step, and the switch that hides it.
 *
 * While the switch is off the feature does not exist as far as the API is
 * concerned: status() returns null, so no response carries a hint of it, and
 * the routes behind RequiresProfileCompletion answer 404. Turning it on makes
 * the step mandatory — the training endpoints refuse until it is done.
 */
class ProfileCompletionService
{
    public function isEnabled(): bool
    {
        return (bool) SystemSetting::get()->profile_completion_enabled;
    }

    public function profileFor(Subscriber $subscriber): ?SubscriberProfile
    {
        return $subscriber->relationLoaded('profile')
            ? $subscriber->profile
            : $subscriber->profile()->first();
    }

    public function isComplete(Subscriber $subscriber): bool
    {
        return (bool) $this->profileFor($subscriber)?->isComplete();
    }

    /**
     * What the app needs to decide whether to show the completion screen —
     * or null when the feature is switched off, so nothing is emitted at all.
     *
     * @return array{is_complete: bool, missing_fields: list<string>}|null
     */
    public function status(Subscriber $subscriber): ?array
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $profile = $this->profileFor($subscriber);

        return [
            'is_complete' => (bool) $profile?->isComplete(),
            'missing_fields' => $profile?->missingFields() ?? SubscriberProfile::REQUIRED_FIELDS,
        ];
    }
}
