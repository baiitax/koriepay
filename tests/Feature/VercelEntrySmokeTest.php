<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Smoke test for the Vercel serverless entrypoint (api/index.php).
 *
 * vercel-php boots the app through this file on every request, bypassing
 * public/index.php. This test walks the same path: capture a request into
 * $_SERVER globals, include api/index.php, and assert a real response is
 * produced with the kernel running.
 */
class VercelEntrySmokeTest extends TestCase
{
    public function test_api_index_boots_the_application(): void
    {
        $root = realpath(__DIR__.'/../..');
        $entry = $root.'/api/index.php';
        $this->assertFileExists($entry);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/login';
        $_SERVER['SERVER_NAME'] = 'localhost';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SCRIPT_FILENAME'] = $entry;

        chdir($root);

        ob_start();
        try {
            include $entry;
            $output = ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            $this->fail('api/index.php threw: '.$e->getMessage());
        }

        $this->assertGreaterThan(500, strlen((string) $output), 'Expected a rendered HTML page from the kernel.');
        $this->assertStringContainsString('wire', (string) $output); // Livewire login page rendered
    }
}
