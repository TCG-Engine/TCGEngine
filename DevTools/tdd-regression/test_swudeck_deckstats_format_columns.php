<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_deckstats_format_columns.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';

$conn = GetLocalMySQLConnection();
$checks = [];

$tables = [
  'deckstats'              => ['deckID','source','version','format'],
  'carddeckstats'          => ['deckID','cardID','source','version','format'],
  'opponentdeckstats'      => ['deckID','leaderID','source','version','format'],
  'opponentnamedbasestats' => ['deckID','leaderID','baseID','source','format'],
];

foreach ($tables as $t => $expectedPk) {
    $col = $conn->query("SHOW COLUMNS FROM `$t` LIKE 'format'")->fetch_assoc();
    $checks["$t.format is varchar"]  = $col && stripos((string)$col['Type'], 'varchar') === 0;
    $checks["$t.format NOT NULL"]    = $col && strtoupper((string)$col['Null']) === 'NO';
    $checks["$t.format def premier"] = $col && strtolower((string)$col['Default']) === 'premier';

    $pk = [];
    $res = $conn->query("SHOW KEYS FROM `$t` WHERE Key_name = 'PRIMARY'");
    while ($r = $res->fetch_assoc()) { $pk[intval($r['Seq_in_index'])] = $r['Column_name']; }
    ksort($pk);
    $checks["$t PK includes format"] = (array_values($pk) === $expectedPk);
}

$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
