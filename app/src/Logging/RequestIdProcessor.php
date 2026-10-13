<?php

namespace App\Logging;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsMonologProcessor]
final class RequestIdProcessor
{
    public function __construct(
        private readonly RequestStack $requests,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $id = $this->requests->getMainRequest()?->headers->get('X-Request-ID');
        if (null !== $id) {
            $record->extra['request_id'] = $id;
        }

        return $record;
    }
}
