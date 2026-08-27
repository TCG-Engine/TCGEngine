<?php
// Fortify base-upgrade query primitives: the three reads + their schema wiring.
// Source-level checks — GameLogic.php cannot be loaded standalone (it needs the whole engine), and a
// source-extracting eval() shim would silently test a stale copy. The BEHAVIOR is covered by the DSL
// case BaseUpgradeCountReflectsAttachments, which exercises the same Subcards read through the engine.
function check($cond, $msg) { if (!$cond) { fwrite(STDERR, "FAIL: $msg\n"); exit(1); } }

$logic = file_get_contents(__DIR__ . '/../../Custom/GameLogic.php');

// Object form — the counter's data source; must tolerate a base with no Subcards at all (every base
// that existed before Fortify) rather than warning or crashing.
check(preg_match('/function BaseUpgradeCount\(\$obj\): int/', $logic) === 1,
      'BaseUpgradeCount($obj): int defined');
// ⚠ UPDATED 2026-08-27. This used to pin the literal body `return is_array($obj->Subcards ?? null) …`.
// The implementation was deliberately rewritten to route through GetUpgradesOnUnit(), which is the
// CANONICAL subcard reader: after a gamestate round-trip Subcards come back as associative ARRAYS rather
// than objects, and GetUpgradesOnUnit normalises them and skips removed/captive entries. So the old
// assertion was pinning a body that no longer exists while its INTENT — tolerate a base with no Subcards
// instead of warning or crashing — is still satisfied, and more thoroughly than before.
// Assert the seam, not the exact expression, so a future refactor of the body does not re-red this.
check(preg_match('/function BaseUpgradeCount\(\$obj\): int \{[^}]*GetUpgradesOnUnit\(\$obj\)/s', $logic) === 1,
      'BaseUpgradeCount reads through GetUpgradesOnUnit (round-trip-safe, null-tolerant)');

// Player-keyed forms — what card logic calls (HMW_061 "if your base is upgraded").
check(preg_match('/function SWUBaseUpgradeCount\(int \$player\): int/', $logic) === 1,
      'SWUBaseUpgradeCount(int $player): int defined');
check(preg_match('/function SWUBaseIsUpgraded\(int \$player\): bool/', $logic) === 1,
      'SWUBaseIsUpgraded(int $player): bool defined');
// GetBase() returns the base ZONE (an array); the base is index 0, matching the "myBase-0" mzID.
// Treating it as a bare object counts the zone rather than the upgrades.
check(preg_match('/function SWUBaseUpgradeCount.*?BaseUpgradeCount\(\$zone\[0\]\)/s', $logic) === 1,
      'SWUBaseUpgradeCount reads the zone\'s index 0, not the zone');

// Schema wiring: the virtual that feeds the badge.
$schema = file_get_contents(__DIR__ . '/../../../Schemas/SWUSim/GameSchema.txt');
check(strpos($schema, 'Virtual: UpgradeCount=BaseUpgradeCount()') !== false,
      'Base zone declares the UpgradeCount virtual');
// It must sit inside the Base zone block, not another zone's.
$baseBlock = substr($schema, strpos($schema, 'Base - CardID'), 600);
check(strpos($baseBlock, 'UpgradeCount=BaseUpgradeCount()') !== false,
      'the virtual sits in the Base zone block');

echo "OK\n";
