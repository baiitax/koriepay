<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * §13/§56 acceptance: "100 simultaneous withdrawal requests from the same
 * wallet → only valid available funds may be consumed."
 *
 * A driver script (in a separate process) creates a shared SQLite file,
 * seeds a ₦10,000 wallet, then races 100 independent PHP worker processes
 * each withdrawing ₦200. The ledger must allow exactly 50, keep every
 * balance ≥ 0, and remain perfectly balanced (Σ debits = Σ credits).
 */
class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_100_parallel_withdrawals_consume_only_available_funds(): void
    {
        $driver = <<<'PHP'
<?php
require __DIR__.'/vendor/autoload.php';

$dbFile = $argv[1];
$amount = $argv[2];
$attempts = (int) $argv[3];
$opening = '10000.00';

@unlink($dbFile);
touch($dbFile);

$env = [
    'APP_ENV' => 'testing',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => $dbFile,
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'BROADCAST_CONNECTION' => 'log',
];
// Set $_SERVER, $_ENV and the process environment — Laravel's Env::get reads
// $_SERVER first (ServerConstAdapter), then $_ENV, then getenv(); PHPUnit's
// <env> vars and CLI env inheritance both populate $_SERVER, so all three
// must be overwritten or the parent's values win.
foreach ($env as $k => $v) { $_SERVER[$k] = $v; $_ENV[$k] = $v; putenv("{$k}={$v}"); }

$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Domain\Accounting\LedgerService;
use App\Domain\Accounting\LedgerAccount;

$pdo = DB::connection()->getPdo();
$pdo->exec('PRAGMA busy_timeout = 30000');
$pdo->exec('PRAGMA journal_mode = WAL');

$kernel->call('migrate:fresh', ['--force' => true]);

$ledger = app(LedgerService::class);
$wallet = LedgerAccount::create([
    'account_type' => 'liability', 'currency_code' => 'NGN',
    'name' => 'Racing Wallet', 'owner_type' => 'user', 'owner_id' => 777,
]);
$cash = LedgerAccount::create([
    'account_type' => 'asset', 'currency_code' => 'NGN',
    'name' => 'Platform Cash', 'is_system' => true,
]);
// Opening: DR Platform Cash / CR Wallet (we owe the customer 10,000)
$ledger->post([
    ['account_id' => $cash->id, 'side' => 'debit', 'amount' => $opening],
    ['account_id' => $wallet->id, 'side' => 'credit', 'amount' => $opening],
], 'opening_balance', 'OPEN-RACE');

$worker = <<<'W'
<?php
require __DIR__.'/vendor/autoload.php';
$dbFile = $argv[1];
$walletId = (int) $argv[2];
$amount = $argv[3];
$cashId = (int) $argv[4];
foreach (['APP_ENV'=>'testing','DB_CONNECTION'=>'sqlite','DB_DATABASE'=>$dbFile,'CACHE_STORE'=>'array','SESSION_DRIVER'=>'array','QUEUE_CONNECTION'=>'sync','BROADCAST_CONNECTION'=>'log'] as $k=>$v) {
    $_SERVER[$k]=$v; $_ENV[$k]=$v; putenv("{$k}={$v}");
}
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use App\Domain\Accounting\LedgerService;
DB::connection()->getPdo()->exec('PRAGMA busy_timeout = 30000');
try {
    // Withdrawal: DR customer wallet (liability↓) / CR platform cash (asset↓)
    app(LedgerService::class)->post([
        ['account_id' => $walletId, 'side' => 'debit', 'amount' => $amount],
        ['account_id' => $cashId, 'side' => 'credit', 'amount' => $amount],
    ], 'withdrawal', null, null, 'race-'.uniqid('', true));
    echo 'OK';
} catch (Throwable $e) {
    echo 'FAIL';
}
W;
$workerFile = __DIR__.'/.race_worker.php';
file_put_contents($workerFile, $worker);

// Spawn in bounded waves (20 per wave, 5 waves = 100 total attempts).
// Each worker is an independent PHP process bootstrapping the app against
// the SAME shared SQLite file — true cross-process contention, bounded so
// the sandbox does not OOM.
$wave = 20;
$success = 0;
$failed = 0;
$started = 0;
while ($started < $attempts) {
    $batch = min($wave, $attempts - $started);
    $procs = [];
    $pipes = [];
    for ($i = 0; $i < $batch; $i++) {
        $cmd = escapeshellarg(PHP_BINARY).' -d memory_limit=256M '.escapeshellarg($workerFile)
            .' '.escapeshellarg($dbFile).' '.$wallet->id.' '.escapeshellarg($amount).' '.$cash->id;
        $procs[] = proc_open($cmd, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $p);
        $pipes[] = $p;
    }
    foreach ($procs as $i => $proc) {
        $out = stream_get_contents($pipes[$i][1]);
        fclose($pipes[$i][1]);
        fclose($pipes[$i][2]);
        proc_close($proc);
        if (str_contains((string) $out, 'OK')) { $success++; } else { $failed++; }
    }
    $started += $batch;
}
@unlink($workerFile);

// Fresh connection to the shared file for verification
$verify = <<<'V'
<?php
require __DIR__.'/vendor/autoload.php';
$dbFile = $argv[1];
$walletId = (int) $argv[2];
foreach (['APP_ENV'=>'testing','DB_CONNECTION'=>'sqlite','DB_DATABASE'=>$dbFile] as $k=>$v) {
    $_SERVER[$k]=$v; $_ENV[$k]=$v; putenv("{$k}={$v}");
}
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$debits = (string) DB::table('ledger_entries')->where('side','debit')->sum('amount');
$credits = (string) DB::table('ledger_entries')->where('side','credit')->sum('amount');
$balance = (string) DB::table('ledger_accounts')->where('id', $walletId)->value('balance');
$openingBalance = (string) DB::table('ledger_accounts')->where('id', $walletId)->value('balance');
echo json_encode(compact('debits', 'credits', 'balance', 'openingBalance'));
V;
$verifyFile = __DIR__.'/.race_verify.php';
file_put_contents($verifyFile, $verify);
$summary = json_decode((string) shell_exec(
    escapeshellarg(PHP_BINARY).' '.escapeshellarg($verifyFile).' '.escapeshellarg($dbFile).' '.$wallet->id
), true);
@unlink($verifyFile);
@unlink($dbFile);

echo json_encode([
    'success' => $success,
    'failed' => $failed,
    'attempts' => $attempts,
    'expected_success' => 50,
    'balance' => $summary['balance'] ?? null,
    'debits' => $summary['debits'] ?? null,
    'credits' => $summary['credits'] ?? null,
    'balanced' => isset($summary['debits'], $summary['credits']) && bccomp($summary['debits'], $summary['credits'], 2) === 0,
]);
PHP;

        $driverFile = base_path('.race_driver.php');
        file_put_contents($driverFile, $driver);
        $raw = shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($driverFile)
            .' '.escapeshellarg(sys_get_temp_dir().'/kp_race.sqlite').' 200.00 100');
        @unlink($driverFile);

        $result = json_decode((string) $raw, true);
        $this->assertIsArray($result, "Driver output: {$raw}");
        $this->assertSame(100, $result['attempts']);
        $this->assertSame(50, $result['success'], 'Exactly 50 of 100 × ₦200 succeed against ₦10,000.');
        $this->assertSame(50, $result['failed']);
        // SQLite returns DECIMAL as numeric ('0'); normalize with bcmath.
        $this->assertSame('0.00', bcadd((string) $result['balance'], '0', 2), 'Wallet projection exhausted exactly, never negative.');
        $this->assertTrue($result['balanced'], 'Σ debits == Σ credits after the race.');
    }
}
