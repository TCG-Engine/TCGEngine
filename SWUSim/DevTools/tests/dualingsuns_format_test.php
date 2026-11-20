<?php
// The 'dualingsuns' format: Twin Suns deckbuilding at two seats, private rooms only, and out of every record path —
// docs/superpowers/specs/2026-11-12-swusim-dualing-suns-design.md.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/dualingsuns_format_test.php
chdir(dirname(__DIR__, 3));
error_reporting(E_ALL & ~E_DEPRECATED);
require_once './AppCore/SWU/Formats.php';
require_once './AppCore/SWU/BoosterSets.php';
require_once './SWUSim/StatsSubmit.php';
require_once './SWUSim/SWUForgeDelivery.php';
require_once './SWUSim/Custom/DeckImport.php';
require_once './SWUSim/Custom/SetupPanels.php';
require_once './SWUSim/GameSetupRules.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

echo "Registered:\n";
$f = SWUGetFormat('dualingsuns');
$check($f !== null, 'dualingsuns is registered');
$check(SWUFormatIsDualingSuns('dualingsuns') && !SWUFormatIsDualingSuns('twinsuns'), 'SWUFormatIsDualingSuns names only dualingsuns');
$check(($f['leaderCount'] ?? null) === 2 && ($f['maxCopies'] ?? null) === 1 && ($f['minDeck'] ?? null) === 80,
    'Twin Suns deckbuilding: 2 leaders, singleton, 80 cards');
$check(SWUFormatSeatRange('dualingsuns') === [2, 2], 'exactly two seats');
$check(SWUFormatLegalSets('dualingsuns') === SWUFormatLegalSets('twinsuns'), 'the Twin Suns standard pool');
$check(($f['banned'] ?? null) === [], 'no bans');
$check(strlen('dualingsuns') <= 16, 'id fits the stats varchar(16)');

echo "PrivateAndUnrecorded:\n";
$check(!in_array('dualingsuns', SWUStatsFormats(true), true) && !in_array('dualingsuns', SWUStatsFormats(false), true), 'not a stats format (write or read)');
$check(!SWUFormatAllowsPublicQueue('dualingsuns', true), 'never in a public queue');
$check(!SWUFormatIsRated('dualingsuns'), 'never rated');
$check(!SWUFormatIsRoomFormat('dualingsuns'), 'not a multi-seat room format');
$check(SWUFormatRecordsNothing('dualingsuns') && SWUFormatRecordsNothing('limited') && !SWUFormatRecordsNothing('premier'),
    'SWUFormatRecordsNothing = Limited + Dualing Suns');
$check(SWUCardPoolFor('dualingsuns', null) === 'dualingsuns', 'a game records dualingsuns as its card pool');
$check(SWUMatchStatsSkip(['format' => 'dualingsuns']) === 'skipped_alternate', 'a Dualing Suns match skips SWUStats as an alternate format');
$check(SWUMatchStatsSkip(['format' => 'limited']) === 'skipped_limited', 'Limited keeps its own skip status');
$check(SWUMatchStatsSkip(['format' => 'premier']) === null, 'a Premier match is not skipped');
$check(SWUForgeShouldEnqueue(['format' => 'dualingsuns']) === false, 'a Dualing Suns game never queues for SWU Forge');
$check(SWUForgeShouldEnqueue(['format' => 'premier']) === true, 'a Premier game still does');

echo "DeckValidation:\n";
$pre = SWUSetupTwinSunsPreCons();
$check($pre !== [], 'Twin Suns pre-cons exist');
$r = SWUResolveDeckInput($pre[0]['input'] ?? '');
$check(!empty($r['success']), 'the first Twin Suns pre-con resolves');
$errs = SWUCheckFormat('dualingsuns', $r['leader'] ?? '', $r['base'] ?? '', $r['mainDeck'] ?? [], $r['sideboard'] ?? []);
$check($errs === [], 'a Twin Suns pre-con is a legal Dualing Suns deck' . ($errs ? ': ' . implode('; ', $errs) : ''));
$oneLeader = is_array($r['leader'] ?? null) ? $r['leader'][0] : ($r['leader'] ?? '');
$errs1 = SWUCheckFormat('dualingsuns', $oneLeader, $r['base'] ?? '', $r['mainDeck'] ?? [], $r['sideboard'] ?? []);
$check((bool)array_filter($errs1, fn($e) => str_contains($e, 'requires exactly 2 leaders')), 'a 1-leader deck is refused');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
