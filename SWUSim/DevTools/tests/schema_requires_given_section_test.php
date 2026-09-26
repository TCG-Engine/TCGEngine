<?php
// ── A schema with NO GIVEN DIRECTIVES must be REFUSED, not silently turned into a default board ─────
//
// Owner report, 2026-09-26: "loading a new schema test for this file did not work." The file was
// SWUSim/Tests/Visual/Chat_4P_WhisperMatrix.md — a VISUAL doc, every line a '#' comment, no GIVEN/WHEN/
// EXPECT sections at all. Pasting it into the Test Schema Editor returned HTTP 200 and
//     {"gameName":1310616,"whenSteps":[],"stepCount":0,"seatCount":2,"liveSeats":[1,2]}
// — a brand-new EMPTY TWO-SEAT game. On screen that is two arenas, "Card" placeholders where the leader
// and base go, and an empty GAME LOG, which reads as a BROKEN BOARD rather than as a rejected input.
// Four such games (1310610-1310613) were created in four minutes before the owner asked what was wrong.
//
// _parse() returned ['ok' => true] unconditionally, so "no GIVEN heading" and "GIVEN heading with
// nothing under it" both produced an empty directive list, and _buildInitialState happily built the
// default two-player state from it. The silence is the defect: a parser that cannot find a single
// directive has not parsed a schema.
//
// ⚠ The guard lives in _parse(), which is BOTH entry points — parseForUI() (the editor) and runFile()
// (the regression suite). Both already branch on 'ok', so a .md dropped under Tests/Cases with a
// mistyped heading now FAILS that file instead of passing as an empty game.
//
//     php -d xdebug.mode=off SWUSim/DevTools/tests/schema_requires_given_section_test.php
$FAILS = 0;
function check($cond, $msg, $extra = null) {
    global $FAILS;
    echo ($cond ? '  ok: ' : '  BAD: ') . $msg . (($cond || $extra === null) ? '' : '  ' . json_encode($extra)) . "\n";
    if (!$cond) $FAILS++;
}

if (!defined('STDERR')) define('STDERR', fopen('php://stderr', 'w'));
chdir(realpath(__DIR__ . '/../../..'));
$_GET['mode'] = 'cli';
if (!function_exists('ConvertMzIDToAbsolute')) { function ConvertMzIDToAbsolute($mzID, $p): string { return ''; } }
if (!function_exists('QueueDamageAnimation')) { function QueueDamageAnimation($t, $a): void {} }
if (!function_exists('QueueRestoreAnimation')) { function QueueRestoreAnimation($t, $a): void {} }
if (!function_exists('QueuePreventedDamageAnimation')) { function QueuePreventedDamageAnimation($t): void {} }
if (!function_exists('QueueShieldBreakAnimation')) { function QueueShieldBreakAnimation($t): void {} }
foreach (['./AccountFiles/AccountSessionAPI.php', './Core/HTTPLibraries.php', './Core/DeterministicRNG.php',
          './Core/CoreZoneModifiers.php', './Core/GameAuth.php', './SWUSim/ZoneClasses.php', './SWUSim/ZoneAccessors.php',
          './SWUSim/GeneratedCode/GeneratedCardDictionaries.php', './SWUSim/GamestateParser.php',
          './SWUSim/Tests/Framework/Assertions.php', './SWUSim/Tests/Framework/Cards.php',
          './SWUSim/Tests/Framework/CommonSetup.php', './SWUSim/Tests/Framework/GameStateBuilder.php',
          './SWUSim/Tests/Framework/GameTestAdapter.php', './SWUSim/Tests/Framework/SchemaTestRunner.php',
          './SWUSim/Tests/Framework/TestRunner.php'] as $f) include_once $f;

// ── 1. THE REPORTED SHAPE. A real Tests/Visual doc that is prose only. ──────────────────────────────
// ⚠ NOT Chat_4P_WhisperMatrix.md any more — that file was the original report, and it has since been
// CONVERTED into a real schema (WithChat:/WithGameLog:, 2026-09-26). Naming it here would pin the bug
// to a file that no longer has it.
$visual = file_get_contents('./SWUSim/Tests/Visual/CardLanguage_LocalizedImages.md');
check(is_string($visual) && strlen($visual) > 500, 'the prose-only Visual doc is readable', strlen((string)$visual));
$r = SchemaTestRunner::parseForUI($visual);
check(empty($r['ok']), 'a prose-only Visual doc is REFUSED by the editor (was: a default two-seat game)', $r);
check(!empty($r['error']) && stripos($r['error'], 'GIVEN') !== false,
      'the refusal names GIVEN, so the paster knows what the file is missing', $r['error'] ?? null);

// THE INVARIANT, so this cannot rot as docs are added or converted: a file that TELLS the reader it is
// not a schema and the parser must agree. Every Tests/Visual doc whose first line carries the
// "NOT A SCHEMA FILE" banner has to be refused, and every other one has to load.
$mismatched = [];
foreach (glob('./SWUSim/Tests/Visual/*.md') as $doc) {
    // "Opens with", not "line 1 exactly": README.md leads with its markdown title. Still tight enough
    // that a banner buried in the middle of a doc does not count as a warning anyone will read.
    $head    = implode('', array_slice(explode("\n", (string)file_get_contents($doc)), 0, 5));
    $claims  = stripos($head, 'NOT A SCHEMA FILE') !== false;
    $refused = empty(SchemaTestRunner::parseForUI((string)file_get_contents($doc))['ok']);
    if ($claims !== $refused) $mismatched[] = basename($doc) . ($claims ? ' (says no, parses)' : ' (says yes, refused)');
}
check($mismatched === [],
      'every Visual doc that declares "NOT A SCHEMA FILE" is refused, and every other one loads', $mismatched);

// And the converted file specifically — this is what the owner originally tried to open.
$r = SchemaTestRunner::parseForUI((string)file_get_contents('./SWUSim/Tests/Visual/Chat_4P_WhisperMatrix.md'));
check(!empty($r['ok']), 'Chat_4P_WhisperMatrix.md now LOADS (it was converted to a schema)', $r['error'] ?? null);
check(count(array_filter($r['given'] ?? [], fn($l) => strpos($l, 'WithChat:') === 0)) === 8
      && count(array_filter($r['given'] ?? [], fn($l) => strpos($l, 'WithGameLog:') === 0)) === 8,
      'and it carries its 8 chat + 8 log directives',
      [count(array_filter($r['given'] ?? [], fn($l) => strpos($l, 'WithChat:') === 0)),
       count(array_filter($r['given'] ?? [], fn($l) => strpos($l, 'WithGameLog:') === 0))]);

// ── 2. THE TWO SHAPES THAT BOTH LAND ON A DEFAULT BOARD ─────────────────────────────────────────────
// No heading at all — a prose/comment file, like the Visual doc above.
$r = SchemaTestRunner::parseForUI("# just a comment\n# and another\n");
check(empty($r['ok']), 'content with NO section headings is refused');

// The heading is there but nothing is under it. This one is nastier: it LOOKS like a schema, and it is
// what a half-written case file looks like mid-edit.
$r = SchemaTestRunner::parseForUI("## GIVEN\n\n## WHEN\n\n## EXPECT\nTURNPLAYER:1\n");
check(empty($r['ok']), 'an EMPTY GIVEN section is refused too — it builds the same default board');

// A GIVEN whose only lines are comments strips to nothing. Same board, same silence.
$r = SchemaTestRunner::parseForUI("## GIVEN\n# CommonSetup: bbw/rrk\n## WHEN\n## EXPECT\nTURNPLAYER:1\n");
check(empty($r['ok']), 'a GIVEN of only commented-out directives is refused');

// ── 3. THE CONTROLS. Without these, "refuse everything" passes every assertion above. ───────────────
$good = "## GIVEN\nCommonSetup: bbw/rrk\nWithGamePhase: ActionPhase\n## WHEN\n## EXPECT\nTURNPLAYER:1\n";
$r = SchemaTestRunner::parseForUI($good);
check(!empty($r['ok']), 'a real two-player schema still parses', $r['error'] ?? null);
check(count($r['given'] ?? []) === 2, 'and its GIVEN directives survive intact', $r['given'] ?? null);
check(($r['expect'] ?? []) === ['TURNPLAYER:1'], 'and so does its EXPECT', $r['expect'] ?? null);

// The four-seat schema the chat fixture actually uses — the one the owner was trying to load.
require_once './DevTools/tdd-regression/fixtures/swusim_chat_http_helpers.php';
$r = SchemaTestRunner::parseForUI(SWUCHAT_SCHEMA_TWINSUNS);
check(!empty($r['ok']), 'the four-seat Twin Suns fixture schema still parses', $r['error'] ?? null);
check(in_array('WithSeatOrder: 1234', $r['given'] ?? [], true),
      'and it still carries WithSeatOrder — the directive that makes it a FOUR-seat board', $r['given'] ?? null);

// A real case file off disk, so the control is not just a string literal written to match the guard.
$real = './SWUSim/Tests/Cases/core/MultiSeat_EliminatedSeatFixture.md';
$r = SchemaTestRunner::parseForUI((string)file_get_contents($real));
check(!empty($r['ok']), "a real Tests/Cases file still parses ({$real})", $r['error'] ?? null);
check(count($r['given'] ?? []) > 0, 'and yields directives');

echo $FAILS === 0 ? "\nALL PASS\n" : "\n{$FAILS} FAILED\n";
exit($FAILS === 0 ? 0 : 1);
