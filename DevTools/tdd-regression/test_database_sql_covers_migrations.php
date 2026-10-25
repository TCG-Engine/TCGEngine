<?php
// php DevTools/tdd-regression/test_database_sql_covers_migrations.php   (pure text check: no DB, any PHP)
//
// A new box loads Database/database.sql and nothing else (newhost/setup-box.sh), so every table and column a
// migration adds must ALSO be in database.sql. On 2026-10-22 the rebuilt swustats.net could not create a single
// deck for a day: 06_ownership_friendlycode.sql had added ownership.friendlyCode, database.sql never got it, and
// CreateDeck.php died in AssignFriendlyCode() (Unknown column 'friendlyCode') after saving an empty ownership row.
// This reads every migration's CREATE TABLE and ALTER TABLE … ADD [COLUMN] and requires each in database.sql.
// Migrations that only change data or types (no new table/column) are not checked here.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root = dirname(__DIR__, 2);
$strip = fn(string $sql) => preg_replace('/--[^\n]*/', '', $sql);
$schema = $strip((string)file_get_contents("$root/Database/database.sql"));

// Columns per table in database.sql: each CREATE TABLE body plus any ALTER TABLE … ADD [COLUMN] it carries.
$tables = [];
if (preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?(\w+)`?\s*\((.*?)\)\s*(?:ENGINE|;)/is', $schema, $m, PREG_SET_ORDER)) {
    foreach ($m as $t) {
        preg_match_all('/^\s*`?(\w+)`?\s+(?:var|int|tiny|small|big|text|char|date|time|float|double|decimal|enum|json|bool|long|medium)/im', $t[2], $cols);
        $tables[strtolower($t[1])] = array_flip(array_map('strtolower', $cols[1]));
    }
}
if (preg_match_all('/ALTER TABLE\s+`?(\w+)`?(.*?);/is', $schema, $m, PREG_SET_ORDER)) {
    foreach ($m as $a) {
        preg_match_all('/ADD(?: COLUMN)?(?: IF NOT EXISTS)?\s+`?(\w+)`?\s+(?:var|int|tiny|small|big|text|char|date|time|float|double|decimal|enum|json|bool|long|medium)/i', $a[2], $cols);
        foreach ($cols[1] as $c) $tables[strtolower($a[1])][strtolower($c)] = true;
    }
}

// Tables a later migration replaced. Each needs a reason, and no code may still read it.
$replaced = [
    'azukideckversionstats' => '08_engine_asset_versioning.sql copies its rows into assetversionstats',
];

$checks = [];
$checks['precondition: database.sql parsed (has ownership, users)'] = isset($tables['ownership'], $tables['users']);
foreach (glob("$root/Database/migrations/*.sql") as $file) {
    $sql = $strip((string)file_get_contents($file));
    $name = basename($file);
    if (preg_match_all('/CREATE TABLE(?: IF NOT EXISTS)?\s+`?(\w+)`?/i', $sql, $m)) {
        foreach ($m[1] as $t) {
            if (isset($replaced[strtolower($t)])) continue;
            $checks["$name: table $t is in database.sql"] = isset($tables[strtolower($t)]);
        }
    }
    if (preg_match_all('/ALTER TABLE\s+`?(\w+)`?(.*?);/is', $sql, $m, PREG_SET_ORDER)) {
        foreach ($m as $a) {
            preg_match_all('/ADD(?: COLUMN)?(?: IF NOT EXISTS)?\s+`?(\w+)`?\s+(?:var|int|tiny|small|big|text|char|date|time|float|double|decimal|enum|json|bool|long|medium)/i', $a[2], $cols);
            foreach ($cols[1] as $c) {
                $checks["$name: column {$a[1]}.$c is in database.sql"] = isset($tables[strtolower($a[1])][strtolower($c)]);
            }
        }
    }
}
// The friendly code's unique index is what keeps two decks from sharing a share link.
$checks['06_ownership_friendlycode: the unique index is in database.sql'] =
    preg_match('/UNIQUE (?:INDEX|KEY)\s+`?idx_ownership_friendlyCode`?\s*\(`?friendlyCode`?\)/i', $schema) === 1;

$fails = array_keys(array_filter($checks, fn($v) => $v !== true));
foreach ($checks as $n => $ok) echo ($ok ? '  ok  ' : '  FAIL ') . $n . "\n";
echo empty($fails) ? "PASS (" . count($checks) . " checks)\n" : "FAIL (" . count($fails) . " of " . count($checks) . ")\n";
exit(empty($fails) ? 0 : 1);
