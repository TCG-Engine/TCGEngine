<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_save_asset_format.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../../Database/ConnectionManager.php';

$checks = [];
$testID = 900000002;
$testType = 999;
$testOwner = 999999999;

// Clean slate.
$conn = GetLocalMySQLConnection();
$conn->query("DELETE FROM ownership WHERE assetIdentifier = $testID AND assetType = $testType");
$conn->close();

// Default: no format passed → 'standard' (the generic shared-code default; SWUDeck's own
// CreateDeck.php always passes an explicit format so this only matters for callers like
// AzukiDeck/GrandArchiveSim/SoulMastersDB that never specify one).
SaveAssetOwnership($testType, $testID, $testOwner);
$asset = LoadAssetData($testType, $testID);
$checks['default format is standard'] = ($asset['format'] ?? null) === 'standard';

$conn = GetLocalMySQLConnection();
$conn->query("DELETE FROM ownership WHERE assetIdentifier = $testID AND assetType = $testType");
$conn->close();

// Explicit format passed at save time.
SaveAssetOwnership($testType, $testID, $testOwner, null, null, 'eternal');
$asset = LoadAssetData($testType, $testID);
$checks['explicit format is saved'] = ($asset['format'] ?? null) === 'eternal';

// UpdateAssetFormat changes it afterward.
$updated = UpdateAssetFormat($testType, $testID, 'twinsuns');
$asset = LoadAssetData($testType, $testID);
$checks['UpdateAssetFormat returns truthy'] = (bool)$updated === true;
$checks['UpdateAssetFormat persists new format'] = ($asset['format'] ?? null) === 'twinsuns';

// Cleanup.
$conn = GetLocalMySQLConnection();
$conn->query("DELETE FROM ownership WHERE assetIdentifier = $testID AND assetType = $testType");
$conn->close();

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
