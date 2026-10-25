<?php

declare(strict_types=1);

const MIGRATION_APPS = ['admin', 'stitch', 'neighborhub'];

function printUsage(): void
{
    echo "Usage:\n";
    echo "  php cmd/migrate.php status [app|all] [--env=development|production]\n";
    echo "  php cmd/migrate.php up [app|all] [--env=development|production]\n";
    echo "  php cmd/migrate.php adopt <app> --yes --schema-reviewed [--env=development|production]\n";
    echo "  php cmd/migrate.php down <app> --yes [--env=development|production]\n";
    echo "  php cmd/migrate.php create <app> <name>\n";
}

function fail(string $message, int $exitCode = 1): never
{
    fwrite(STDERR, "Error: {$message}\n");
    exit($exitCode);
}

function migrationDirectory(string $app): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'html' . DIRECTORY_SEPARATOR . 'apps'
        . DIRECTORY_SEPARATOR . $app . DIRECTORY_SEPARATOR . 'migrations';
}

function createMigration(string $app, string $name): void
{
    if (!in_array($app, MIGRATION_APPS, true)) {
        fail('Unknown app. Choose: ' . implode(', ', MIGRATION_APPS) . '.');
    }
    if (!preg_match('/^[a-z][a-z0-9_-]*$/', $name)) {
        fail('Migration name must start with a lowercase letter and contain only lowercase letters, digits, hyphens, or underscores.');
    }

    $directory = migrationDirectory($app);
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        fail("Could not create migration directory: {$directory}");
    }

    $timestamp = date('YmdHis');
    $path = $directory . DIRECTORY_SEPARATOR . $timestamp . '_' . $name . '.php';
    if (file_exists($path)) {
        fail("Migration already exists: {$path}");
    }

    $contents = <<<'PHP'
<?php

return [
    'up' => static function (PDO $db): void {
        // Apply the schema change here.
    },
    'down' => static function (PDO $db): void {
        // Reverse the schema change only when it is safe.
    },
];
PHP;
    if (file_put_contents($path, $contents . PHP_EOL, LOCK_EX) === false) {
        fail("Could not write migration file: {$path}");
    }

    echo "Created {$path}\n";
}

function bootstrapDatabase(string $environment): PDO
{
    $root = dirname(__DIR__);
    $html = $root . DIRECTORY_SEPARATOR . 'html';

    if (!defined('MB_RUNNING')) {
        define('MB_RUNNING', true);
    }
    if (!defined('ROOT_PATH')) {
        define('ROOT_PATH', $html);
    }
    if (!defined('STORAGE_PATH')) {
        define('STORAGE_PATH', $root . DIRECTORY_SEPARATOR . 'storage');
    }

    $_SERVER['HTTP_HOST'] = $environment === 'development'
        ? 'mediabrain.app.local'
        : (getenv('APP_HOST') ?: 'cli.invalid');
    $_SERVER['HTTPS'] = $environment === 'development' ? 'on' : 'off';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'CLI';

    if (!chdir($html)) {
        fail("Could not change working directory to {$html}");
    }

    require_once $root . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'util.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'SecurityHeaders.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'EventLogger.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'MaintenanceMode.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'FileStorageManager.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'User.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'app.php';
    require_once $html . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'DatabaseMigrationRunner.class.php';

    $app = App::getInstance('admin');
    if (!$app->db instanceof PDO) {
        fail('The application database connection is not available.');
    }

    return $app->db;
}

function printStatus(string $app, array $status): void
{
    echo "[{$app}]\n";
    if ($status === []) {
        echo "  No migration files found.\n";
        return;
    }

    foreach ($status as $migration) {
        $state = $migration['applied'] ? 'applied' : 'pending';
        $date = $migration['applied_at'] === null ? '' : ' (' . $migration['applied_at'] . ')';
        echo "  {$state}: {$migration['id']}{$date}\n";
    }
}

function main(array $arguments): void
{
    array_shift($arguments);
    if ($arguments === [] || in_array($arguments[0], ['help', '--help', '-h'], true)) {
        printUsage();
        return;
    }

    $command = array_shift($arguments);
    $yes = in_array('--yes', $arguments, true);
    $schemaReviewed = in_array('--schema-reviewed', $arguments, true);
    $arguments = array_values(array_filter(
        $arguments,
        static fn(string $argument): bool => !in_array($argument, ['--yes', '--schema-reviewed'], true)
    ));
    $environment = getenv('APP_ENV') === 'development' ? 'development' : 'production';
    foreach ($arguments as $key => $argument) {
        if (str_starts_with($argument, '--env=')) {
            $environment = substr($argument, 6);
            unset($arguments[$key]);
        }
    }
    $arguments = array_values($arguments);
    if (!in_array($environment, ['development', 'production'], true)) {
        fail('Environment must be development or production.');
    }

    if ($command === 'create') {
        if (count($arguments) !== 2) {
            printUsage();
            fail('The create command requires an app and a migration name.');
        }
        createMigration($arguments[0], $arguments[1]);
        return;
    }

    if (!in_array($command, ['status', 'up', 'down', 'adopt'], true)) {
        printUsage();
        fail("Unknown command: {$command}");
    }

    if (in_array($command, ['down', 'adopt'], true) && !$yes) {
        fail("The {$command} command requires explicit confirmation. Re-run with --yes.");
    }
    if ($command === 'adopt' && !$schemaReviewed) {
        fail('Baseline adoption requires manual review of column types, defaults, indexes, constraints, and engine. Re-run with --schema-reviewed after verifying these against the baseline.');
    }

    $appArgument = $arguments[0] ?? 'all';
    if (count($arguments) > 1) {
        printUsage();
        fail('Too many command arguments.');
    }
    $apps = $appArgument === 'all' ? MIGRATION_APPS : [$appArgument];
    foreach ($apps as $app) {
        if (!in_array($app, MIGRATION_APPS, true)) {
            fail("Unknown app '{$app}'. Choose: " . implode(', ', MIGRATION_APPS) . ', or all.');
        }
    }
    if (in_array($command, ['down', 'adopt'], true) && count($apps) !== 1) {
        fail("The {$command} command operates on one app at a time.");
    }

    if (in_array($command, ['up', 'down', 'adopt'], true)) {
        foreach ($apps as $app) {
            $files = glob(migrationDirectory($app) . DIRECTORY_SEPARATOR . '*.php');
            if ($files === false || $files === []) {
                fail("No migration files found for {$app}. Create and review the baseline before running {$command}.");
            }
        }
    }

    $db = bootstrapDatabase($environment);
    foreach ($apps as $app) {
        $runner = new DatabaseMigrationRunner($db, $app, migrationDirectory($app));
        if ($command === 'status') {
            printStatus($app, $runner->getStatus());
            continue;
        }
        if ($command === 'up') {
            $applied = $runner->migrate();
            echo "[{$app}] " . ($applied === [] ? 'No pending migrations.' : 'Applied: ' . implode(', ', $applied)) . "\n";
            continue;
        }
        if ($command === 'adopt') {
            $adopted = $runner->adoptBaseline();
            echo "[{$app}] Adopted {$adopted}; application tables and data were not changed.\n";
            continue;
        }

        $rolledBack = $runner->rollbackLast();
        echo "[{$app}] " . ($rolledBack === null ? 'No applied migrations.' : "Rolled back {$rolledBack}.") . "\n";
    }
}

try {
    main($argv);
} catch (Throwable $exception) {
    fail($exception->getMessage());
}
