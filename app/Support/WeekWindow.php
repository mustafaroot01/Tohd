<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Seven days, Saturday to Friday, in the app's timezone.
 *
 * Carbon 3 has no global week-start setting, so every caller that needs "this
 * week" goes through here rather than remembering to pass the day twice.
 */
final class WeekWindow
{
    private function __construct(
        public readonly Carbon $start,
        public readonly Carbon $end,
    ) {}

    /** The week containing $date (any format Carbon parses; defaults to today). */
    public static function containing(Carbon|string|null $date = null): self
    {
        $tz = config('app.timezone');
        $day = ($date instanceof Carbon ? $date->copy() : Carbon::parse($date ?? 'today', $tz))
            ->setTimezone($tz)
            ->startOfDay();

        $start = $day->startOfWeek(self::startsOn());

        return new self($start, $start->copy()->addDays(6)->endOfDay());
    }

    public static function startsOn(): int
    {
        return match (strtolower((string) config('scoring.week_starts_on', 'saturday'))) {
            'sunday' => CarbonInterface::SUNDAY,
            'monday' => CarbonInterface::MONDAY,
            'tuesday' => CarbonInterface::TUESDAY,
            'wednesday' => CarbonInterface::WEDNESDAY,
            'thursday' => CarbonInterface::THURSDAY,
            'friday' => CarbonInterface::FRIDAY,
            default => CarbonInterface::SATURDAY,
        };
    }

    public function previous(): self
    {
        return self::containing($this->start->copy()->subDay());
    }

    public function next(): self
    {
        return self::containing($this->end->copy()->addDay());
    }

    /** @return list<string> the seven Y-m-d dates, in order */
    public function dates(): array
    {
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $this->start->copy()->addDays($i)->toDateString();
        }

        return $dates;
    }

    public function contains(CarbonInterface $at): bool
    {
        return $at->between($this->start, $this->end);
    }

    public function isCurrent(): bool
    {
        return $this->contains(now());
    }

    /** Days of the week that have already happened (7 for a past week). */
    public function daysElapsed(): int
    {
        if ($this->end->isPast()) {
            return 7;
        }
        if ($this->start->isFuture()) {
            return 0;
        }

        return (int) $this->start->diffInDays(now()->startOfDay()) + 1;
    }

    /** @return array{start: string, end: string, is_current: bool, days_elapsed: int} */
    public function toArray(): array
    {
        return [
            'start' => $this->start->toDateString(),
            'end' => $this->end->toDateString(),
            'is_current' => $this->isCurrent(),
            'days_elapsed' => $this->daysElapsed(),
        ];
    }
}
