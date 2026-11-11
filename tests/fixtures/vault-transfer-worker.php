<?php
define('MB_RUNNING', true);
require __DIR__ . '/../mocks/App.php';
require __DIR__ . '/../../html/includes/models/storage.model.php';
require __DIR__ . '/../../html/apps/vault/includes/models/vault.model.php';

$db = new PDO('sqlite:' . $argv[1]);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('PRAGMA busy_timeout = 5000');
App::getInstance()->db = $db;

try {
    Vault::transfer(1, (int)$argv[2], '40.00', 'concurrent test');
    echo 'success';
} catch (DomainException $e) {
    echo 'insufficient';
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage());
    exit(1);
}
