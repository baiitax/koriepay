<?php

namespace Tests\Unit;

use App\Database\NeonPostgresConnector;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

class NeonPostgresConnectorTest extends TestCase
{
    #[Test]
    public function dsn_forwards_sslmode_and_channel_binding(): void
    {
        $dsn = $this->dsn([
            'sslmode' => 'require',
            'channel_binding' => 'require',
        ]);

        $this->assertStringContainsString('sslmode=require', $dsn);
        $this->assertStringContainsString('channel_binding=require', $dsn);
    }

    #[Test]
    public function dsn_omits_channel_binding_when_unset(): void
    {
        $dsn = $this->dsn(['sslmode' => 'require']);

        $this->assertStringContainsString('sslmode=require', $dsn);
        $this->assertStringNotContainsString('channel_binding', $dsn);
    }

    #[Test]
    public function dsn_adds_neon_endpoint_id_for_pooled_host(): void
    {
        $dsn = $this->dsn([
            'sslmode' => 'require',
            'host' => 'ep-tiny-field-ae9wh1bi-pooler.c-2.us-east-2.aws.neon.tech',
        ]);

        $this->assertStringContainsString("options='endpoint=ep-tiny-field-ae9wh1bi'", $dsn);
    }

    private function dsn(array $config): string
    {
        $connector = new NeonPostgresConnector;
        $method = new ReflectionMethod(NeonPostgresConnector::class, 'addSslOptions');

        return $method->invoke($connector, "pgsql:host=example;dbname='neondb'", $config);
    }
}
