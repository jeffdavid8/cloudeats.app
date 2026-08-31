<?php
require_once 'html/apps/admin/includes/PermissionsMatrix.php';

$pm = new PermissionsMatrix();
$summary = $pm->getPermissionsSummary();

echo "=== PERMISSIONS STRUCTURE ===\n";
echo json_encode($summary, JSON_PRETTY_PRINT);
?>