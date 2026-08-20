<?php

namespace Tests\Fakes;

use App\Contracts\SmsGatewayInterface;

class FakeSmsGateway implements SmsGatewayInterface
{
    /** @var array<int, array{phone: string, message: string}> */
    public array $sent = [];

    public function send(string $e164Phone, string $message): void
    {
        $this->sent[] = ['phone' => $e164Phone, 'message' => $message];
    }

    public function lastCodeFor(string $phone): ?string
    {
        foreach (array_reverse($this->sent) as $entry) {
            if ($entry['phone'] === $phone) {
                return $entry['message'];
            }
        }

        return null;
    }
}
