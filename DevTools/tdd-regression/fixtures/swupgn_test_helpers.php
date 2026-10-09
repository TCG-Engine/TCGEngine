<?php
// Shared helpers for the test_swupgn_*.php guards (the SWU-PGN reader in AppCore/SWU/SwuPgn/).
// Pure PHP, no engine, no DB — these tests run identically on the host CLI and in the container.

require_once __DIR__ . '/../../../AppCore/SWU/SwuPgn/SwuPgn.php';

const SWUPGN_FIXTURES = __DIR__ . '/swupgn';

$GLOBALS['swupgnFails'] = 0;
$GLOBALS['swupgnChecks'] = 0;

function swupgnCheck(bool $ok, string $msg): void {
  $GLOBALS['swupgnChecks']++;
  if (!$ok) $GLOBALS['swupgnFails']++;
  echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n";
}

// Run $fn and report whether it threw a Throwable whose message matches $pattern.
function swupgnThrows(callable $fn, string $pattern): bool {
  try { $fn(); } catch (Throwable $t) { return preg_match($pattern, $t->getMessage()) === 1; }
  return false;
}

function swupgnNoThrow(callable $fn): bool {
  try { $fn(); } catch (Throwable $t) { echo "  threw: " . $t->getMessage() . "\n"; return false; }
  return true;
}

// Canonical JSON, byte-identical to the oracle that produced the *.expected.json fixtures: keys
// sorted, and an EMPTY object written as [] because a decoded PHP array cannot tell {} from [].
function swupgnCanon($x) {
  if (!is_array($x)) return $x;
  if ($x === [] || array_is_list($x)) return array_map('swupgnCanon', $x);
  ksort($x, SORT_STRING);
  return array_map('swupgnCanon', $x);
}

function swupgnCanonJson($x): string {
  return json_encode(swupgnCanon($x), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS);
}

function swupgnFixture(string $name): string {
  return file_get_contents(SWUPGN_FIXTURES . '/' . $name);
}

// Events as a reader receives them: JSON-decoded, so ints are ints and objects are arrays.
function swupgnEvents(array $events): array {
  return json_decode(json_encode($events), true);
}

function swupgnFinish(): void {
  $f = $GLOBALS['swupgnFails'];
  $n = $GLOBALS['swupgnChecks'];
  echo $f === 0 ? "PASS: all $n checks\n" : "FAIL: $f of $n checks failed\n";
  exit($f === 0 ? 0 : 1);
}
