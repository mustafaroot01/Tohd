<?php

return [
    /*
    |--------------------------------------------------------------------------
    | The grade
    |--------------------------------------------------------------------------
    |
    | A game here is an animation the child watches, so the trained skill is
    | sustained attention — the measure is how long they stayed with it against
    | how long the game asks for. Everything below is decided on the server; the
    | app's own numbers are never trusted for a grade, only ever to lower it.
    |
    | grade          = floor( attended ÷ required × max_score ), capped at max_score
    | required_score = round( game.success_threshold × max_score )
    | passed         = grade ≥ required_score
    |
    | floor, not round: 45 s of a 60 s game is 7.5 tenths, and a child who held
    | 75 % must not be shown "8/10 — passed" against an 80 % bar.
    |
    */

    /** Grades run 0..max_score, matching the "معيار النجاح (من 10)" field. */
    'max_score' => 10,

    /**
     * Which reading becomes the child's grade for a game.
     *
     *   best_of_day      — the best attempt today (attention is a ceiling, and a
     *                      distracted retry does not lower what the child showed)
     *   latest           — the most recent attempt
     *   best_all_time    — the best attempt ever
     */
    'strategy' => env('SCORING_STRATEGY', 'best_of_day'),

    /**
     * When true the app's reported duration is taken as-is. Off by default: the
     * server measures the attempt from the token it issued to the moment the
     * result arrives, and credits whichever is smaller.
     */
    'trust_client_duration' => env('SCORING_TRUST_CLIENT_DURATION', false),

    /** Watching past the game's length does not earn more than full marks. */
    'cap_at_game_duration' => true,

    /*
    |--------------------------------------------------------------------------
    | Attempts
    |--------------------------------------------------------------------------
    */

    /**
     * An attempt token stays redeemable for the game's length plus this grace.
     * Long enough for a slow network after the animation ends, short enough
     * that a token cannot be kept in a drawer.
     */
    'attempt_grace_seconds' => env('SCORING_ATTEMPT_GRACE_SECONDS', 600),

    /**
     * An attempt shorter than this is recorded but does not count as a real
     * try: it neither adds a failure nor unlocks skipping. Without it, three
     * one-second completions would "earn" a skip in three seconds.
     *
     * The threshold is the larger of the two values, per game.
     */
    'min_counted_seconds' => env('SCORING_MIN_COUNTED_SECONDS', 5),
    'min_counted_ratio' => env('SCORING_MIN_COUNTED_RATIO', 0.25),

    /** Hard ceiling on attempts per game per day — a runaway client, not a child. */
    'max_attempts_per_day' => env('SCORING_MAX_ATTEMPTS_PER_DAY', 200),

    /*
    |--------------------------------------------------------------------------
    | Advancing through the day
    |--------------------------------------------------------------------------
    */

    /** A game counts as done only when the child actually passed it. */
    'require_pass_to_advance' => env('SCORING_REQUIRE_PASS', true),

    /**
     * Counted failures today after which the child may skip the game.
     *
     * These are children training on attention difficulties: a hard lock turns
     * one hard game into a dead end for the whole day and pushes them out of the
     * app. Skipping is recorded as SKIPPED, never as a pass, so reports stay
     * honest and a specialist can see the game was too demanding.
     *
     * 0 disables skipping entirely.
     */
    'attempts_before_unlock' => env('SCORING_ATTEMPTS_BEFORE_UNLOCK', 3),

    /*
    |--------------------------------------------------------------------------
    | Calendar
    |--------------------------------------------------------------------------
    |
    | "Today" cuts at midnight in config('app.timezone'). The weekly report is
    | the Iraqi working week: Saturday through Friday.
    |
    */
    'week_starts_on' => env('SCORING_WEEK_STARTS_ON', 'saturday'),

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    |
    | The log channel that receives one JSON line per graded attempt and per
    | skip — the only per-attempt record kept. 'null' silences it (tests).
    |
    */
    'audit_channel' => env('SCORING_AUDIT_CHANNEL', 'attempts'),
];
