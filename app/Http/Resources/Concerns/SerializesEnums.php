<?php

namespace App\Http\Resources\Concerns;

use BackedEnum;

/**
 * Every resource used to spell enum serialisation out by hand as
 *
 *     $this->status?->value ?? (string) $this->status
 *
 * which is not only repeated a dozen times, it turns a null status into an
 * empty string instead of null. This does the same job in one place, correctly.
 */
trait SerializesEnums
{
    protected function enumValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof BackedEnum ? (string) $value->value : (string) $value;
    }
}
