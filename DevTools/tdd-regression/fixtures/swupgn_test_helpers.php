<?php
// Shared helpers for the SWU-PGN reader tests (DevTools/tdd-regression/test_swupgn_*.php).
//
// Pure PHP: no engine, no DB, no HTTP. Every PHP warning/notice is promoted to an exception so a
// sloppy array access in the reader fails the test instead of scrolling past. A shutdown hook
// prints a FAIL summary line if the script dies (fatal, uncaught exception) before it finishes, so
// the last line of output is always `PASS …` or `FAIL …`.

error_reporting(E_ALL);
set_error_handler(function (int $no, string $str, string $file, int $line) {
    throw new ErrorException($str, 0, $no, $file, $line);
});

require_once __DIR__ . '/../../../AppCore/SWU/SwuPgn/SwuPgn.php';

$GLOBALS['__swupgn_t'] = ['pass' => 0, 'fail' => 0, 'name' => basename($_SERVER['argv'][0] ?? 'test'), 'done' => false];

register_shutdown_function(function () {
    $t = $GLOBALS['__swupgn_t'];
    if ($t['done']) return;
    $err = error_get_last();
    echo "\nFAIL: {$t['name']} died before finishing (" . ($t['pass'] + $t['fail']) . " checks ran"
        . ($err ? '; ' . $err['message'] : '') . ")\n";
    exit(1);
});

function SwuPgnTestCheck(bool $ok, string $msg, $detail = null): void
{
    if ($ok) {
        $GLOBALS['__swupgn_t']['pass']++;
        echo "PASS: $msg\n";
        return;
    }
    $GLOBALS['__swupgn_t']['fail']++;
    echo "FAIL: $msg\n";
    if ($detail !== null) {
        echo '      ' . str_replace("\n", "\n      ", is_string($detail) ? $detail : SwuPgnTestDump($detail)) . "\n";
    }
}

function SwuPgnTestDump($v): string
{
    return (string)json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR);
}

// Deep equality that ignores assoc-key ORDER but is strict on scalar types; an empty JSON object
// (stdClass) equals an empty array.
function SwuPgnTestSameValue($a, $b): bool
{
    if ($a instanceof stdClass && !get_object_vars($a)) $a = [];
    if ($b instanceof stdClass && !get_object_vars($b)) $b = [];
    if (is_array($a) && is_array($b)) {
        if (count($a) !== count($b)) return false;
        if (array_is_list($a) !== array_is_list($b)) return false;
        foreach ($a as $k => $v) {
            if (!array_key_exists($k, $b) || !SwuPgnTestSameValue($v, $b[$k])) return false;
        }
        return true;
    }
    return $a === $b;
}

function SwuPgnTestEq($got, $expected, string $msg): void
{
    $ok = SwuPgnTestSameValue($got, $expected);
    SwuPgnTestCheck($ok, $msg, $ok ? null : "expected " . SwuPgnTestDump($expected) . "\n got      " . SwuPgnTestDump($got));
}

// Runs $fn and reports whether it threw; returns [threw?, message].
function SwuPgnTestThrows(callable $fn): array
{
    try {
        $fn();
        return [false, null];
    } catch (Throwable $e) {
        return [true, get_class($e) . ': ' . $e->getMessage()];
    }
}

function SwuPgnTestFinish(): void
{
    $t = &$GLOBALS['__swupgn_t'];
    $t['done'] = true;
    $total = $t['pass'] + $t['fail'];
    if ($t['fail'] === 0) {
        echo "\nPASS: {$t['name']} — all $total checks passed\n";
        exit(0);
    }
    echo "\nFAIL: {$t['name']} — {$t['fail']} of $total checks failed\n";
    exit(1);
}

// ---- builders --------------------------------------------------------------------------------

function SwuPgnTestHeaderTags(array $overrides = []): array
{
    $tags = [
        'Game' => 'SWU-PGN/1.0', 'GameId' => 'test-game', 'Date' => '2026-06-16T00:00:00Z',
        'CardPool' => 'SOR', 'Engine' => 'petranaki@test', 'Seed' => '0',
        'P1Id' => 'sha256:aaaa', 'P2Id' => 'sha256:bbbb', 'P1' => 'Player 1', 'P2' => 'Player 2',
        'P1Leader' => 'SOR#010', 'P1Base' => 'SOR#028', 'P2Leader' => 'SOR#005', 'P2Base' => 'SOR#020',
        'Result' => 'Incomplete', 'Reason' => 'Test', 'Rounds' => '1',
    ];
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($tags[$k]); else $tags[$k] = $v;
    }
    return $tags;
}

function SwuPgnTestHeaderText(array $tags): string
{
    $out = '';
    foreach ($tags as $k => $v) {
        $out .= '[' . $k . ' "' . str_replace(['\\', '"'], ['\\\\', '\\"'], $v) . "\"]\n";
    }
    return $out;
}

function SwuPgnTestJsonLine($v): string
{
    return json_encode($v, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

// Builds a whole .swupgn text. $o: header (overrides), cards (list of records), decks, setup,
// annotations, story (list of lines). Events go in %%% EVENTS.
function SwuPgnTestFile(array $events, array $o = []): string
{
    $txt = SwuPgnTestHeaderText(SwuPgnTestHeaderTags($o['header'] ?? []));
    if (isset($o['story'])) $txt .= "\n%%% STORY\n" . implode("\n", $o['story']) . "\n";
    foreach (['decks' => 'DECKS', 'cards' => 'CARDS', 'setup' => 'SETUP'] as $k => $banner) {
        if (!isset($o[$k])) continue;
        $txt .= "\n%%% $banner\n";
        foreach ($o[$k] as $r) $txt .= SwuPgnTestJsonLine($r) . "\n";
    }
    $txt .= "\n%%% EVENTS\n";
    foreach ($events as $e) $txt .= SwuPgnTestJsonLine($e) . "\n";
    if (isset($o['annotations'])) {
        $txt .= "\n%%% ANNOTATIONS\n";
        foreach ($o['annotations'] as $r) $txt .= SwuPgnTestJsonLine($r) . "\n";
    }
    return $txt;
}

// Gives every event without a seq one ("R1.A.<n>"), so hand-made streams stay short.
function SwuPgnTestSeq(array $events, string $prefix = 'R1.A.'): array
{
    $n = 0;
    foreach ($events as $i => $e) {
        $n++;
        if (!isset($e['seq'])) $events[$i] = ['seq' => $prefix . $n] + $e;
    }
    return $events;
}

function SwuPgnTestSeatKeyframe(int $seat, array $over = []): array
{
    $s = [
        'seat' => $seat, 'baseHp' => 30, 'baseMaxHp' => 30, 'handSize' => 0, 'hand' => [],
        'resourcesReady' => 0, 'resourcesExhausted' => 0, 'credits' => 0, 'hasForce' => false,
        'discard' => [], 'cards' => [],
    ];
    foreach ($over as $k => $v) {
        if ($v === null) unset($s[$k]); else $s[$k] = $v;
    }
    return $s;
}

// A complete two-seat keyframe. $p1/$p2 override seat fields; $root overrides top-level fields.
function SwuPgnTestKeyframe(array $p1 = [], array $p2 = [], array $root = []): array
{
    $kf = ['round' => 1, 'phase' => 'action', 'initiative' => 1, 'initiativeTaken' => false,
        'players' => ['1' => SwuPgnTestSeatKeyframe(1, $p1), '2' => SwuPgnTestSeatKeyframe(2, $p2)]];
    foreach ($root as $k => $v) $kf[$k] = $v;
    return $kf;
}

// JSON round trip through the reader's own parser shape (empty objects stay objects), so a
// keyframe built in PHP looks exactly like one read from a file.
function SwuPgnTestViaJson(array $v)
{
    $doc = SwuPgnParse(SwuPgnTestFile([['seq' => 'R1.A.1', 't' => 'X', 'v' => $v]]));
    return $doc['events'][0]['v'];
}

function SwuPgnTestCard(string $id, string $zone = 'ground', array $over = []): array
{
    $c = ['id' => $id, 'zone' => $zone, 'damage' => 0, 'exhausted' => false, 'upgrades' => [],
        'shields' => 0, 'experience' => 0, 'statusTokens' => new stdClass(), 'captured' => []];
    foreach ($over as $k => $v) {
        if ($v === null) unset($c[$k]); else $c[$k] = $v;
    }
    return $c;
}
