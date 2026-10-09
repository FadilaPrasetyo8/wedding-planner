<?php

namespace App\Logging;

use Monolog\LogRecord;

class RedactSensitiveContext
{
    private const REDACTED = '[REDACTED]';

    /** @var array<int, string> */
    private const SENSITIVE_PARTS = [
        'password',
        'token',
        'authorization',
        'cookie',
        'secret',
        'signature',
        'storage',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            context: $this->redact($record->context),
            extra: $this->redact($record->extra),
        );
    }

    /** @param array<mixed> $value */
    private function redact(array $value): array
    {
        $result = [];

        foreach ($value as $key => $item) {
            $keyString = strtolower((string) $key);
            $isSensitive = collect(self::SENSITIVE_PARTS)
                ->contains(fn (string $part): bool => str_contains($keyString, $part));

            $result[$key] = $isSensitive
                ? self::REDACTED
                : (is_array($item) ? $this->redact($item) : $item);
        }

        return $result;
    }
}
