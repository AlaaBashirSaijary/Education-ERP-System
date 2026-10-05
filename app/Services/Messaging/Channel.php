<?php

namespace App\Services\Messaging;

interface Channel
{
    /** Send $message to $phone; throw on failure. */
    public function send(string $phone, string $message): void;
}
