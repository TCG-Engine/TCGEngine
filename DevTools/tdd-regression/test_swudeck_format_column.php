<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_format_column.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';

$conn = GetLocalMySQLConnection();
$checks = [];

$col = null;
$result = $conn->query("SHOW COLUMNS FROM ownership LIKE 'format'");
if ($result) $col = $result->fetch_assoc();
$checks['format column exists'] = $col !== null;
$checks['format column defaults to premier'] = $col && strtolower((string)$col['Default']) === 'premier';

// A freshly inserted row that doesn't specify format should default to 'premier'.
$testID = 900000001;
$conn->query("DELETE FROM ownership WHERE assetIdentifier = $testID AND assetType = 999"); // clean slate
$stmt = $conn->prepare("INSERT INTO ownership (assetType, assetIdentifier, assetOwner, assetStatus) VALUES (999, ?, 999999999, 1)");
$stmt->bind_param("i", $testID);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("SELECT format FROM ownership WHERE assetIdentifier = ? AND assetType = 999");
$stmt->bind_param("i", $testID);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$checks['new row defaults to premier'] = $row && $row['format'] === 'premier';

$conn->query("DELETE FROM ownership WHERE assetIdentifier = $testID AND assetType = 999");
$conn->close();

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
