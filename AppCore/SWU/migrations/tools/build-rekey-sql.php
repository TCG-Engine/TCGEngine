<?php
// Emits the SET_NNN identity migration itself, as plain SQL, from the LIVE schema.
//
//   php AppCore/SWU/migrations/tools/build-rekey-sql.php --out=AppCore/SWU/migrations
//
// Writes TWO files, because the swap must be separable from the verify:
//   02_rekey_stats.sql   build the _new tables and assert their counter totals   (safe; reversible
//                        by dropping the _new tables — the live tables are untouched)
//   03_swap.sql          RENAME the _new tables into place                       (the point of no
//                        easy return; gated on 02 having completed clean)
// A dry run is exactly "apply 02, then drop the _new tables" — a real rehearsal, on real data, with
// real timings, that cannot swap.
//
// Needs a database connection, so it runs on a dev box (or through a zz page) — NOT under LAMPP's
// CLI PHP, which has no mysqli. That is deliberate: this runs AHEAD of the window and produces a
// pure-SQL artefact, so nothing during the window depends on PHP.
//
// Why generate rather than hand-write: the spec's inventory table, database.sql, the prod clone and
// prod itself have all drifted from each other. Hand-writing 6 tables x ~40 columns against any one
// of them bakes in whichever is wrong. Reading information_schema cannot be stale — and the emitted
// SQL carries a SCHEMA ASSERTION so that if the box it eventually runs on differs from the box it
// was generated against, it aborts before touching a single row instead of migrating half a table.
//
// Shape, per spec §6 "It is an aggregation, not a rename": the UUID -> SET_NNN map is MANY-TO-ONE
// (reprints fold via CardIDOverride), so two existing rows can land on one new primary key. A naive
// UPDATE would both raise a duplicate-key error partway through AND silently discard one row's
// counters. Every table therefore migrates as:
//
//     CREATE TABLE x_new LIKE x  ->  aggregating INSERT ... SELECT ... GROUP BY  ->  verify  ->  RENAME
//
// applied uniformly, including to tables whose identifier is not in the primary key (completedgame).
// Those cannot collide and an UPDATE would be cheaper, but the rebuild is what makes rollback a
// RENAME rather than a restore-from-dump, and "the window is the whole risk".
//
// Design: docs/superpowers/specs/2026-08-03-swudeck-setnnn-identity-migration-design.md §6

$repoRoot = dirname(__DIR__, 4);
require_once $repoRoot . '/Database/ConnectionManager.php';
require_once __DIR__ . '/../lib/IdentifierMap.php';

$outDir = null;
foreach (array_slice($argv, 1) as $arg) {
    if (strpos($arg, '--out=') === 0) $outDir = rtrim(substr($arg, 6), '/');
    else { fwrite(STDERR, "unknown argument: $arg\n"); exit(2); }
}
if ($outDir === null) { fwrite(STDERR, "usage: build-rekey-sql.php --out=<dir>\n"); exit(2); }
if (!is_dir($outDir)) { fwrite(STDERR, "FATAL: not a directory: $outDir\n"); exit(2); }

$conn = GetLocalMySQLConnection();
if (!$conn) { fwrite(STDERR, "FATAL: no database connection.\n"); exit(1); }
$db = $conn->query('SELECT DATABASE()')->fetch_row()[0];

// ── Read the real schema ────────────────────────────────────────────────────
$cols = [];   // table => [ column => ['type' => .., 'numeric' => bool] ]
$res = $conn->query("SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE
                       FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "'
                      ORDER BY TABLE_NAME, ORDINAL_POSITION");
while ($r = $res->fetch_assoc()) {
    $cols[$r['TABLE_NAME']][$r['COLUMN_NAME']] = [
        'type'    => $r['COLUMN_TYPE'],
        'numeric' => in_array($r['DATA_TYPE'], ['int','bigint','smallint','tinyint','mediumint','decimal','float','double'], true),
    ];
}

$pks = [];    // table => [column, ...]
$res = $conn->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                      WHERE TABLE_SCHEMA = '" . $conn->real_escape_string($db) . "'
                        AND CONSTRAINT_NAME = 'PRIMARY'
                      ORDER BY TABLE_NAME, ORDINAL_POSITION");
while ($r = $res->fetch_assoc()) $pks[$r['TABLE_NAME']][] = $r['COLUMN_NAME'];

// Group the migration targets by table: one rebuild per table, however many id columns it has.
$byTable = [];
foreach (SWUMigrationTargets() as [$table, $column, $poly]) {
    if (!isset($cols[$table][$column])) continue;         // absent on this box — reported below
    $byTable[$table][$column] = $poly;
}

$absent = [];
foreach (SWUMigrationTargets() as [$table, $column, $poly]) {
    if (!isset($cols[$table][$column])) $absent[] = "$table.$column";
}

if (!$byTable) { fwrite(STDERR, "FATAL: none of the target tables exist in `$db`.\n"); exit(1); }

$q = fn($s) => '`' . str_replace('`', '``', (string)$s) . '`';
$out = [];      // 02_rekey_stats.sql — build + verify, live tables untouched
$swap = [];     // 03_swap.sql        — the RENAMEs, gated on 02 completing clean

$out[] = "-- SET_NNN identity migration — stats re-key";
$out[] = "-- GENERATED by AppCore/SWU/migrations/tools/build-rekey-sql.php against schema `$db`.";
$out[] = "-- Do not hand-edit. Regenerate if the schema changes.";
$out[] = "--";
$out[] = "-- REQUIRES 01_id_map.sql to have been applied first (creates `swu_id_map`).";
$out[] = "--";
$out[] = "-- Each table: CREATE _new LIKE -> aggregating INSERT -> RENAME. Counters are SUMmed because";
$out[] = "-- the map is many-to-one (reprints fold), so rows MERGE. The INNER JOIN against swu_id_map";
$out[] = "-- is what drops class-3 rows: they are not deleted, they are simply not selected, and they";
$out[] = "-- remain readable in the _old table until it is dropped after the window.";
if ($absent) {
    $out[] = "--";
    $out[] = "-- Not present on the box this was generated against (no migration step emitted):";
    foreach ($absent as $a) $out[] = "--   $a";
}
$out[] = "";
$out[] = "SET SESSION sql_mode = CONCAT(@@sql_mode, ',STRICT_ALL_TABLES');";
$out[] = "SET SESSION innodb_lock_wait_timeout = 300;";
$out[] = "";

// ── The assertion helper ────────────────────────────────────────────────────
// SIGNAL, not a division-by-zero trick. MySQL evaluates `1/0` in a SELECT to NULL rather than
// raising — verified against this stack — so an assertion built that way PRINTS "NULL" and carries
// straight on into the DDL. SIGNAL aborts the client (which stops on error by default) and carries
// the explanation in the error text, which is what you actually want at 2am.
$out[] = "-- ── Assertion helper ──────────────────────────────────────────────────────";
$out[] = "-- Raises and stops the run. Do NOT replace this with an expression-based check: MySQL";
$out[] = "-- evaluates `1/0` in a SELECT to NULL, so that style of assertion never fires.";
$out[] = "DELIMITER \$\$";
$out[] = "DROP PROCEDURE IF EXISTS `swu_assert`\$\$";
$out[] = "CREATE PROCEDURE `swu_assert`(IN cond BOOLEAN, IN msg VARCHAR(400))";
$out[] = "BEGIN";
$out[] = "  IF NOT cond OR cond IS NULL THEN";
$out[] = "    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;";
$out[] = "  END IF;";
$out[] = "END\$\$";
$out[] = "DELIMITER ;";
$out[] = "";

// ── Schema assertion ────────────────────────────────────────────────────────
// If the box this runs on has a different column list from the one it was generated against, every
// INSERT below is wrong. Fail here, before any DDL, rather than half-migrate.
$out[] = "-- ── Schema assertion ──────────────────────────────────────────────────────";
$out[] = "-- Aborts if this box's schema differs from the one this file was generated against.";
$out[] = "-- A failure here means: regenerate this file on THIS box, do not edit it by hand.";
$out[] = "CALL `swu_assert`((SELECT COUNT(*) FROM information_schema.TABLES";
$out[] = "                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'swu_id_map') = 1,";
$out[] = "  'swu_id_map is missing — apply 01_id_map.sql first.');";
foreach ($byTable as $table => $idCols) {
    $sig = implode(',', array_keys($cols[$table]));
    $out[] = sprintf(
        "CALL `swu_assert`((SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION)\n"
      . "                     FROM information_schema.COLUMNS\n"
      . "                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '%s') = '%s',\n"
      . "  'SCHEMA DRIFT in %s — regenerate 02_rekey_stats.sql on THIS box; do not hand-edit it.');",
        $table, $conn->real_escape_string($sig), $table);
}
$out[] = "";

// ── Per-table migration ─────────────────────────────────────────────────────
foreach ($byTable as $table => $idCols) {
    $allCols = array_keys($cols[$table]);
    $pk      = $pks[$table] ?? [];
    // Grouping keys: the primary key, with identifier members replaced by their MAPPED value.
    // A table with no PK (or whose PK excludes every identifier) cannot collide, but is rebuilt the
    // same way so that rollback stays a RENAME — see the header note.
    $groupBy = [];
    $select  = [];

    // One JOIN per identifier column. Aliased m0, m1, ... in target order.
    $joins = [];
    $aliasFor = [];
    $i = 0;
    foreach ($idCols as $col => $poly) {
        $alias = "m$i";
        $aliasFor[$col] = $alias;
        $joins[] = sprintf("  JOIN `swu_id_map` %s ON %s.`oldID` = t.%s", $alias, $alias, $q($col));
        $i++;
    }

    foreach ($allCols as $col) {
        if (isset($aliasFor[$col])) {
            // 'keep' writes the ORIGINAL value back: class-2 base colours must survive verbatim,
            // and the ci collation means 'Green' matched the map's 'green' row.
            $expr = sprintf("IF(%s.`disposition` = 'keep', t.%s, %s.`newID`)",
                            $aliasFor[$col], $q($col), $aliasFor[$col]);
            $select[] = "$expr AS " . $q($col);
            if (in_array($col, $pk, true)) $groupBy[] = $expr;
            continue;
        }
        if (in_array($col, $pk, true)) {
            $select[] = "t." . $q($col);
            $groupBy[] = "t." . $q($col);
            continue;
        }
        // Non-key columns. Counters SUM; anything else takes MAX, which is a no-op when the group
        // has one row and is at least deterministic when it does not.
        $select[] = $cols[$table][$col]['numeric']
            ? sprintf("SUM(t.%s) AS %s", $q($col), $q($col))
            : sprintf("MAX(t.%s) AS %s", $q($col), $q($col));
    }

    $out[] = "-- ══ $table ═══════════════════════════════════════════════════════════";
    $out[] = "--    identifier column(s): " . implode(', ', array_keys($idCols));
    $out[] = "--    primary key:          " . ($pk ? implode(', ', $pk) : '(none)');
    $out[] = "DROP TABLE IF EXISTS " . $q($table . '_new') . ";";
    $out[] = "CREATE TABLE " . $q($table . '_new') . " LIKE " . $q($table) . ";";
    $out[] = "";
    $out[] = "INSERT INTO " . $q($table . '_new') . " (" . implode(', ', array_map($q, $allCols)) . ")";
    $out[] = "SELECT";
    $out[] = "  " . implode(",\n  ", $select);
    $out[] = "FROM " . $q($table) . " t";
    $out[] = implode("\n", $joins);
    if ($groupBy) $out[] = "GROUP BY " . implode(', ', $groupBy);
    $out[] = ";";
    $out[] = "";

    // ── Verification: counter totals must be identical. Row counts legitimately DROP (rows merge,
    //    class-3 rows are not selected), so comparing row counts would be a false alarm; comparing
    //    SUMs is what actually proves nothing was lost. Class-3 rows are excluded from the "before"
    //    side by the same JOIN, so the two sides are comparable.
    $counters = array_values(array_filter($allCols,
        fn($c) => $cols[$table][$c]['numeric'] && !in_array($c, $pk, true) && !isset($aliasFor[$c])));
    if ($counters) {
        $beforeSel = [];
        $afterSel  = [];
        foreach ($counters as $c) {
            $beforeSel[] = sprintf("COALESCE(SUM(t.%s),0) AS %s", $q($c), $q($c));
            $afterSel[]  = sprintf("COALESCE(SUM(%s),0) AS %s", $q($c), $q($c));
        }
        // Aggregation preserves TOTALS while row counts legitimately drop (rows merge; class-3 rows
        // are never selected), so comparing row counts would false-alarm and comparing SUMs is what
        // actually proves nothing was lost. The "before" side reuses the same JOIN, so class-3 rows
        // are excluded from both sides and the two are comparable.
        //
        // This ABORTS rather than printing a delta table for a human to check. A SUM-less migration
        // loses counters without erroring — that is the risk the spec calls out as silent — and an
        // operator scanning 44 columns of zeroes at 2am is not a control.
        $cmp = implode("\n         AND ", array_map(fn($c) => sprintf("b.%s = a.%s", $q($c), $q($c)), $counters));
        $out[] = "-- Counter totals must be IDENTICAL across the migration. Aborts if not.";
        $out[] = "CALL `swu_assert`((SELECT $cmp";
        $out[] = "    FROM (SELECT " . implode(', ', $beforeSel);
        $out[] = "            FROM " . $q($table) . " t";
        $out[] = implode("\n", $joins);
        $out[] = "         ) b";
        $out[] = "    JOIN (SELECT " . implode(', ', $afterSel) . " FROM " . $q($table . '_new') . ") a),";
        $out[] = "  'COUNTER MISMATCH in $table — totals changed. STOP: do not RENAME, investigate "
               . $table . "_new.');";
        $out[] = "";
    }

    $out[] = "-- Rows NOT carried forward (class 3 + merged duplicates), for the record:";
    $out[] = "SELECT (SELECT COUNT(*) FROM " . $q($table) . ") AS before_rows,";
    $out[] = "       (SELECT COUNT(*) FROM " . $q($table . '_new') . ") AS after_rows;";
    $out[] = "";
    $swap[] = "-- $table. Rollback is the reverse RENAME, valid while {$table}_old still exists.";
    $swap[] = "CALL `swu_assert`((SELECT COUNT(*) FROM information_schema.TABLES";
    $swap[] = "                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}_new') = 1,";
    $swap[] = "  '{$table}_new is missing — run 02_rekey_stats.sql first, and do not swap if it aborted.');";
    $swap[] = "RENAME TABLE " . $q($table) . " TO " . $q($table . '_old') . ",";
    $swap[] = "             " . $q($table . '_new') . " TO " . $q($table) . ";";
    $swap[] = "";
}

$out[] = "-- The live tables are still untouched at this point. Nothing above swapped anything:";
$out[] = "-- apply 03_swap.sql to do that, or DROP the *_new tables to abandon the run.";
$out[] = "";

// ── 03_swap.sql ─────────────────────────────────────────────────────────────
$head = [];
$head[] = "-- SET_NNN identity migration — SWAP";
$head[] = "-- GENERATED by AppCore/SWU/migrations/tools/build-rekey-sql.php against schema `$db`.";
$head[] = "--";
$head[] = "-- Apply ONLY after 02_rekey_stats.sql completed with no error. 02 aborts on schema drift";
$head[] = "-- and on any counter mismatch, so \"it ran clean\" is a real gate, not a formality.";
$head[] = "--";
$head[] = "-- Rollback, while the *_old tables still exist:";
$head[] = "--   RENAME TABLE x TO x_new, x_old TO x;   -- per table, reverse order";
$head[] = "-- Keep every *_old table until the window closes. Dropping them makes the class-3";
$head[] = "-- disposition final and removes the RENAME-based rollback.";
$head[] = "";
$head[] = "SET SESSION innodb_lock_wait_timeout = 300;";
$head[] = "";
$head[] = "-- swu_assert is created by 02; recreate it so this file also stands alone.";
$head[] = "DELIMITER \$\$";
$head[] = "DROP PROCEDURE IF EXISTS `swu_assert`\$\$";
$head[] = "CREATE PROCEDURE `swu_assert`(IN cond BOOLEAN, IN msg VARCHAR(400))";
$head[] = "BEGIN";
$head[] = "  IF NOT cond OR cond IS NULL THEN";
$head[] = "    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = msg;";
$head[] = "  END IF;";
$head[] = "END\$\$";
$head[] = "DELIMITER ;";
$head[] = "";

file_put_contents("$outDir/02_rekey_stats.sql", implode("\n", $out));
file_put_contents("$outDir/03_swap.sql", implode("\n", array_merge($head, $swap)));

fwrite(STDERR, sprintf("wrote %s/02_rekey_stats.sql (%d tables)\n", $outDir, count($byTable)));
fwrite(STDERR, sprintf("wrote %s/03_swap.sql\n", $outDir));
if ($absent) fwrite(STDERR, "skipped (absent on this box): " . implode(', ', $absent) . "\n");
$conn->close();
