<?php

namespace Tests\Concerns;

use App\Actions\Attempts\CompleteAttemptAction;
use App\Actions\Attempts\IssueAttemptAction;
use App\Models\Axis;
use App\Models\Game;
use App\Models\Skill;
use App\Models\Subscriber;
use App\Support\AttemptResult;

/**
 * Play games the way the app does — start, let server time pass, complete —
 * without an HTTP round trip. Time moves with travelTo(), so every attempt
 * is graded against a real elapsed interval on the (frozen) server clock.
 */
trait PlaysGames
{
    protected static int $phoneSeq = 0;

    /** A 60-second game with an 8/10 bar: six seconds of attention per tenth. */
    protected function makeGame(array $overrides = []): Game
    {
        $axis = Axis::firstOrCreate(['slug' => 'attention'], ['name' => 'الانتباه', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $skill = Skill::firstOrCreate(['slug' => 'sustained'], ['axis_id' => $axis->id, 'name' => 'الانتباه المستمر', 'status' => 'ACTIVE', 'sort_order' => 1]);
        $n = Game::count() + 1;

        return Game::create(array_merge([
            'code' => "G-$n", 'name' => "لعبة $n", 'slug' => "g-$n", 'type' => 'TAP',
            'axis_id' => $axis->id, 'skill_id' => $skill->id,
            'level' => 1, 'difficulty' => 'easy', 'min_age' => 3, 'max_age' => 8,
            'duration_seconds' => 60, 'status' => 'PUBLISHED', 'version' => 1,
            'config' => ['success_threshold' => 0.8, 'attempts' => 10],
        ], $overrides));
    }

    protected function makeChild(?string $phone = null): Subscriber
    {
        $phone ??= '+96477000'.str_pad((string) ++self::$phoneSeq, 5, '0', STR_PAD_LEFT);

        return Subscriber::create([
            'name' => 'فارس', 'phone' => $phone,
            'password' => bcrypt('x'), 'status' => 'ACTIVE', 'phone_verified_at' => now(),
        ]);
    }

    protected function issueToken(Subscriber $child, Game $game, ?string $dayId = null): string
    {
        return app(IssueAttemptAction::class)->execute($child, $game, $dayId)['attempt_token'];
    }

    protected function completeToken(Subscriber $child, Game $game, string $token, ?int $claimed = null): AttemptResult
    {
        $input = ['attempt_token' => $token];
        if ($claimed !== null) {
            $input['duration_seconds'] = $claimed;
        }

        return app(CompleteAttemptAction::class)->execute($child, $game, $input);
    }

    /** Start, stay with the game for $seconds of server time, complete. */
    protected function play(Subscriber $child, Game $game, int $seconds, ?int $claimed = null, ?string $dayId = null): AttemptResult
    {
        $token = $this->issueToken($child, $game, $dayId);
        $this->travelTo(now()->addSeconds($seconds));

        return $this->completeToken($child, $game, $token, $claimed);
    }

    /** @return array<string, string> */
    protected function bearer(Subscriber $child): array
    {
        return ['Authorization' => 'Bearer '.$child->createToken('t')->plainTextToken];
    }
}
