<?php

namespace Tests\Feature;

use Tests\TestCase;

class ServerlessBootTest extends TestCase
{
    public function test_prepare_runtime_fills_remote_addr_from_forwarded_for(): void
    {
        require_once base_path('bootstrap/serverless.php');

        $prev = $_SERVER['REMOTE_ADDR'] ?? null;
        unset($_SERVER['REMOTE_ADDR']);
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.10, 10.0.0.1';

        try {
            $storage = koriepay_prepare_serverless_runtime(base_path());
            $this->assertNull($storage, 'non-Vercel boots must not remap storage');
            $this->assertSame('203.0.113.10', $_SERVER['REMOTE_ADDR']);
        } finally {
            if ($prev === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $prev;
            }
            unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
    }

    public function test_database_url_is_aliased_to_db_url_on_vercel(): void
    {
        require_once base_path('bootstrap/serverless.php');

        $keys = ['VERCEL', 'DB_URL', 'DATABASE_URL', 'DB_CONNECTION', 'DB_SSLMODE', 'DB_CHANNEL_BINDING', 'APP_KEY'];
        $saved = [];
        foreach ($keys as $key) {
            $saved[$key] = [
                'env' => getenv($key),
                '_ENV' => $_ENV[$key] ?? null,
                '_SERVER' => $_SERVER[$key] ?? null,
            ];
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }

        putenv('VERCEL=1');
        $_ENV['VERCEL'] = '1';
        $url = 'postgresql://user:pass@example-pooler.neon.tech/neondb?sslmode=require';
        putenv('DATABASE_URL='.$url);
        $_ENV['DATABASE_URL'] = $url;

        try {
            koriepay_prepare_serverless_runtime(base_path());
            $this->assertSame($url, getenv('DB_URL'));
            $this->assertSame('pgsql', getenv('DB_CONNECTION'));
            $this->assertSame('require', getenv('DB_SSLMODE'));
            $this->assertSame('require', getenv('DB_CHANNEL_BINDING'));
        } finally {
            foreach ($saved as $key => $state) {
                if ($state['env'] === false) {
                    putenv($key);
                } else {
                    putenv($key.'='.$state['env']);
                }
                if ($state['_ENV'] === null) {
                    unset($_ENV[$key]);
                } else {
                    $_ENV[$key] = $state['_ENV'];
                }
                if ($state['_SERVER'] === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $state['_SERVER'];
                }
            }
        }
    }
}
