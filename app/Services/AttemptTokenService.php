<?php

namespace App\Services;

use App\Exceptions\InvalidAttemptTokenException;
use App\Models\CurriculumDay;
use App\Models\Game;
use App\Models\Subscriber;
use App\Support\AttemptToken;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Starting a game writes nothing. The server hands the app a sealed note —
 * who, which game, when on the server's clock — and grades the attempt from
 * that note when it comes back. Crypt is authenticated encryption (AES-256-CBC
 * + HMAC under APP_KEY): the note cannot be read, forged, or edited.
 */
class AttemptTokenService
{
    public const VERSION = 1;

    /** Real tokens are ~400 bytes; anything larger is not one of ours. */
    public const MAX_LENGTH = 1024;

    public function issue(Subscriber $subscriber, Game $game, ?CurriculumDay $day, ?Carbon $now = null): array
    {
        $now ??= now();

        $token = new AttemptToken(
            subscriberId: $subscriber->id,
            gameId: $game->id,
            curriculumDayId: $day?->id,
            startedUs: (int) $now->getPreciseTimestamp(6),
            nonce: bin2hex(random_bytes(8)),
        );

        $sealed = Crypt::encryptString(json_encode([
            'v' => self::VERSION,
            's' => $token->subscriberId,
            'g' => $token->gameId,
            'd' => $token->curriculumDayId,
            't' => $token->startedUs,
            'n' => $token->nonce,
        ], JSON_THROW_ON_ERROR));

        return ['token' => $sealed, 'attempt' => $token];
    }

    /**
     * @throws InvalidAttemptTokenException on anything that is not a token we issued
     */
    public function parse(string $sealed, ?Carbon $now = null): AttemptToken
    {
        $now ??= now();

        if ($sealed === '' || strlen($sealed) > self::MAX_LENGTH) {
            throw new InvalidAttemptTokenException;
        }

        try {
            $payload = json_decode(Crypt::decryptString($sealed), true, 8, JSON_THROW_ON_ERROR);
        } catch (DecryptException|\JsonException) {
            throw new InvalidAttemptTokenException;
        }

        if (! is_array($payload)
            || ($payload['v'] ?? null) !== self::VERSION
            || ! Str::isUuid($payload['s'] ?? null)
            || ! Str::isUuid($payload['g'] ?? null)
            || ! (($payload['d'] ?? null) === null || Str::isUuid($payload['d']))
            || ! is_int($payload['t'] ?? null) || $payload['t'] <= 0
            || ! is_string($payload['n'] ?? null) || strlen($payload['n']) !== 16
        ) {
            throw new InvalidAttemptTokenException;
        }

        // a token from the future can only be a forgery or a badly skewed server
        if ($payload['t'] > (int) $now->getPreciseTimestamp(6) + 5_000_000) {
            throw new InvalidAttemptTokenException;
        }

        return new AttemptToken(
            subscriberId: $payload['s'],
            gameId: $payload['g'],
            curriculumDayId: $payload['d'],
            startedUs: $payload['t'],
            nonce: $payload['n'],
        );
    }
}
