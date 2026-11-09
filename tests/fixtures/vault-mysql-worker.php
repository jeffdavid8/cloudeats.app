<?php

define('MB_RUNNING', true);
require __DIR__ . '/../mocks/App.php';
require __DIR__ . '/../../html/includes/models/storage.model.php';
require __DIR__ . '/../../html/apps/vault/includes/models/vault.model.php';

$db = new PDO(getenv('VAULT_TEST_MYSQL_DSN'), getenv('VAULT_TEST_MYSQL_USER'), getenv('VAULT_TEST_MYSQL_PASSWORD'), [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
App::getInstance()->db = $db;
echo "ready\n";
flush();

try {
    Vault::transfer(1, (int)$argv[1], '40.00', 'concurrent test');
    echo 'success';
} catch (DomainException $e) {
    echo 'insufficient';
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage());
    exit(1);
}
