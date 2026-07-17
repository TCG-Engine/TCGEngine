<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_completedgame_migration.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';

$conn = GetLocalMySQLConnection();
$checks = [];

$col = null;
$result = $conn->query("SHOW COLUMNS FROM completedgame LIKE 'Format'");
if ($result) $col = $result->fetch_assoc();
$checks['Format column exists']      = $col !== null;
$checks['Format is varchar']         = $col && stripos((string)$col['Type'], 'varchar') === 0;
$checks['Format is NOT NULL']        = $col && strtoupper((string)$col['Null']) === 'NO';
$checks['Format defaults to premier']= $col && strtolower((string)$col['Default']) === 'premier';

// A row inserted without specifying Format must default to 'premier'
// (protects legacy inserters and encodes the backfill intent).
$sentinel = 'ZZMIGTEST_W';
$conn->query("DELETE FROM completedgame WHERE WinningHero = '$sentinel'");
$stmt = $conn->prepare("INSERT INTO completedgame (WinningHero, LosingHero, NumTurns) VALUES (?, 'ZZMIGTEST_L', 1)");
$stmt->bind_param("s", $sentinel);
$stmt->execute();
$stmt->close();
$row = $conn->query("SELECT Format FROM completedgame WHERE WinningHero = '$sentinel' LIMIT 1")->fetch_assoc();
$checks['unspecified Format defaults premier'] = $row && $row['Format'] === 'premier';
$conn->query("DELETE FROM completedgame WHERE WinningHero = '$sentinel'");

$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
