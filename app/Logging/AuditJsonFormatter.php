<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;

class AuditJsonFormatter
{
    public function __invoke($logger): void
    {
        foreach ($logger->getHandlers() as $h) {
            $h->setFormatter(new JsonFormatter(
                JsonFormatter::BATCH_MODE_JSON,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ));
        }
    }
}
