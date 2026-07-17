<?php
// http://localhost:3100/TCGEngine/DevTools/tdd-regression/test_swudeck_metastats_format_columns.php
header('Content-Type: text/plain');
include_once __DIR__ . '/../../Database/ConnectionManager.php';
$conn = GetLocalMySQLConnection();
$checks = [];
$tables = [
  'deckmetastats'        => ['leaderID','baseID','week','format'],
  'cardmetastats'        => ['cardID','week','format'],
  'deckmetamatchupstats' => ['leaderID','baseID','opponentLeaderID','opponentBaseID','week','format'],
];
foreach ($tables as $t => $expectedPk) {
    $col = $conn->query("SHOW COLUMNS FROM `$t` LIKE 'format'")->fetch_assoc();
    $checks["$t.format varchar"]  = $col && stripos((string)$col['Type'], 'varchar') === 0;
    $checks["$t.format NOT NULL"] = $col && strtoupper((string)$col['Null']) === 'NO';
    $checks["$t.format premier"]  = $col && strtolower((string)$col['Default']) === 'premier';
    $pk = [];
    $res = $conn->query("SHOW KEYS FROM `$t` WHERE Key_name='PRIMARY'");
    while ($r = $res->fetch_assoc()) { $pk[intval($r['Seq_in_index'])] = $r['Column_name']; }
    ksort($pk);
    $checks["$t PK has format"] = (array_values($pk) === $expectedPk);
}
$conn->close();
$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL: " . implode(', ', $fails) . "\n";
