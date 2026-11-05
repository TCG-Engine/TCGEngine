<?php
// The 'limited' format: registered, private-room only, and out of every stats path —
// docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-design.md §3.1, §3.9.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/limited_format_test.php
chdir(dirname(__DIR__, 3));
require_once './AppCore/SWU/Formats.php';
require_once './AppCore/SWU/BoosterSets.php';
require_once './SWUDeck/Custom/DeckFormats.php';
require_once './SWUSim/StatsSubmit.php';
require_once './SWUSim/SWUForgeDelivery.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

// SWUGetFormat's whitelist as it stood before Limited — adding a key would change every format's shape.
$premierKeys = ['id', 'displayName', 'legalSets', 'banned', 'legalRarities', 'copyExceptions', 'deckSizeModifiers', 'minDeck',
                'maxCopies', 'unrestricted', 'leaderCount', 'enabled', 'publicQueue', 'localMode', 'minPlayers', 'maxPlayers',
                'teams', 'uniqueTeamLeaders', 'rated', 'queueOnly', 'queueTypes'];

echo "Registered:\n";
$check(SWUFormatIsLimited('limited') && !SWUFormatIsLimited('premier'), 'SWUFormatIsLimited names only limited');
$check(SWUFormatIsRegistered('limited'), 'limited is registered');
$f = SWUGetFormat('limited');
$check(($f['minDeck'] ?? null) === 30, 'minimum deck 30');
$check(($f['legalSets'] ?? null) === array_keys(SWUBoosterSets()), 'legal sets = the booster sets');
$check(!SWUFormatIsRated('limited'), 'never rated');
$check(!SWUFormatAllowsPublicQueue('limited'), 'never in a public queue');
$check(strlen('limited') <= 16, 'id fits the stats varchar(16)');

echo "HiddenFromStatsAndMenus:\n";
$check(!in_array('limited', SWUStatsFormats(true), true) && !in_array('limited', SWUStatsFormats(false), true), 'not a stats format (write or read)');
$menuFormats = [];
foreach (SWUMenuTree() as $g) foreach ($g['options'] as $o) {
    if (isset($o['format'])) $menuFormats[] = $o['format'];
    foreach ($o['pools'] as $p) $menuFormats[] = $p['format'];
}
$check($menuFormats !== [] && !in_array('limited', $menuFormats, true), 'not a leaf of the format menu');
$check(!in_array('limited', SWUDeckBuildableFormats(), true), 'not a SWUDeck buildable format');

echo "OtherFormatsUnchanged:\n";
$check(array_keys(SWUGetFormat('premier')) === $premierKeys, 'SWUGetFormat keys unchanged');

echo "StatsSkips:\n";
$check(SWUMatchStatsSkip(['format' => 'limited']) === 'skipped_limited', 'a Limited match skips SWUStats');
$check(SWUMatchStatsSkip(['format' => 'premier']) === null, 'a Premier match does not');
$check(SWUForgeShouldEnqueue(['format' => 'limited']) === false, 'a Limited game never queues for SWU Forge');
$check(SWUForgeShouldEnqueue(['format' => 'premier']) === true, 'a Premier game still does');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
