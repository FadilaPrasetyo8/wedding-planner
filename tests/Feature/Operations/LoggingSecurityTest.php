<?php

namespace Tests\Feature\Operations;

use App\Logging\RedactSensitiveContext;
use Monolog\Level;
use Monolog\LogRecord;
use Tests\TestCase;

class LoggingSecurityTest extends TestCase
{
    public function test_sensitive_nested_context_is_redacted(): void
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable,
            channel: 'testing',
            level: Level::Info,
            message: 'request',
            context: [
                'user' => ['name' => 'Ayu', 'password' => 'do-not-log'],
                'authorization' => 'Bearer secret',
                'nested' => ['storage_secret' => 'private'],
            ],
        );

        $redacted = (new RedactSensitiveContext)($record);

        $this->assertSame('Ayu', $redacted->context['user']['name']);
        $this->assertSame('[REDACTED]', $redacted->context['user']['password']);
        $this->assertSame('[REDACTED]', $redacted->context['authorization']);
        $this->assertSame('[REDACTED]', $redacted->context['nested']['storage_secret']);
    }
}
