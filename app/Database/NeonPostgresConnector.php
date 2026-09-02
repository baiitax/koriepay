<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector;

/**
 * Laravel's stock Postgres DSN only forwards sslmode/sslcert/sslkey/sslrootcert.
 * Neon also needs channel_binding (SCRAM) and, on older libpq (vercel-php),
 * an explicit endpoint= option because SNI is missing.
 */
class NeonPostgresConnector extends PostgresConnector
{
    protected function addSslOptions($dsn, array $config)
    {
        $dsn = parent::addSslOptions($dsn, $config);

        if (! empty($config['channel_binding'])) {
            $dsn .= ';channel_binding='.$config['channel_binding'];
        }

        $host = $config['host'] ?? '';
        if (is_string($host) && str_contains($host, '.neon.tech') && ! str_contains($dsn, 'endpoint=')) {
            if (preg_match('/^(ep-[a-z0-9-]+)/i', $host, $m)) {
                $endpoint = preg_replace('/-pooler$/i', '', $m[1]);
                $dsn .= ";options='endpoint=".$endpoint."'";
            }
        }

        return $dsn;
    }
}
