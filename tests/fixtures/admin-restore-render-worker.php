<?php

define('MB_RUNNING', true);
$tables = ['selected_table', 'other_table'];
$backupFiles = ['backup.json', 'quoted"file.json'];
$backupError = null;
require __DIR__ . '/../../html/apps/admin/views/pages/restore_db.php';
