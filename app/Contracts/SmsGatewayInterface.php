<?php

namespace App\Contracts;

interface SmsGatewayInterface
{
    /**
     * Send an SMS message to the given E.164 phone number.
     *
     * @throws \App\Exceptions\OtpDeliveryFailedException
     */
    public function send(string $e164Phone, string $message): void;
}
