<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_key_indicator3.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../AccountFiles/AccountDatabaseAPI.php';
include_once __DIR__ . '/../../Database/ConnectionManager.php';

$checks = [];
$testID = 900000003;
$testType = 999;

$conn = GetLocalMySQLConnection();
$conn->query("DELETE FROM ownership WHERE assetIdentifier = $testID AND assetType = $testType");
$conn->close();

SaveAssetOwnership($testType, $testID, 999999999);
SetAssetKeyIdentifier($testType, $testID, 3, 'TEST_UUID_123');
$asset = LoadAssetData($testType, $testID);
$checks['keyIndicator3 column exists and is settable'] = ($asset['keyIndicator3'] ?? null) === 'TEST_UUID_123';

// keyIndicator1/2 untouched by a keyIndicator3 write.
$checks['keyIndicator1 unaffected'] = ($asset['keyIndicator1'] ?? null) === null;

$conn = GetLocalMySQLConnection();
$conn->query("DELETE FROM ownership WHERE assetIdentifier = $testID AND assetType = $testType");
$conn->close();

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
