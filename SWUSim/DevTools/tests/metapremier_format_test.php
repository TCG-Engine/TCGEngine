<?php
// metapremier format policy — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §2.1, §2.4.
//   docker exec -w /var/www/html/TCGEngine -e XDEBUG_MODE=off otmtcge-swusim-web-server-1 php SWUSim/DevTools/tests/metapremier_format_test.php
chdir(dirname(__DIR__, 3));
require_once './AppCore/SWU/Formats.php';
require_once './AppCore/SWU/DeckValidation.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };

$mp = SWUGetFormat('metapremier');
$pr = SWUGetFormat('premier');
$check($mp !== null, 'metapremier resolves');
if ($mp === null) { echo "1+ FAILED\n"; exit(1); }
$check($mp['displayName'] === 'Meta Premier', 'display name');
$check($mp['legalSets'] === $pr['legalSets'] && $mp['banned'] === $pr['banned'], 'card pool == premier');
$check(SWUFormatIsRated('metapremier') && !SWUFormatIsRated('premier'), 'only metapremier is rated');
$check(SWUFormatIsQueueOnly('metapremier') && !SWUFormatIsQueueOnly('premier'), 'only metapremier is queue-only');
$check(SWUFormatAllowsQueueType('metapremier', 'bo3') === true, 'metapremier bo3 allowed');
$check(SWUFormatAllowsQueueType('metapremier', 'bo1') === false, 'metapremier bo1 switched off');
$check(SWUFormatAllowedQueueTypes('metapremier') === ['bo3'], 'allowed list = [bo3]');
$check(SWUFormatAllowsQueueType('premier', 'bo1') && SWUFormatAllowsQueueType('premier', 'bo3'), 'premier allows both');
$check(SWUFormatAllowsQueueType('premier', 'bo7') === false, 'unknown queue type never allowed');
$check(SWUFormatIsPreview('metapremier') === false, 'metapremier is never a preview format');
// FEATFLAG_GLICKO2 — the rated queue is switched OFF ('enabled' => false in Formats.php). While off, assert that it is
// hidden and refused; when the flag turns on, the full checks below run again with no edit needed here.
$glickoOn = !empty($mp['enabled']);
if ($glickoOn) {
    $check(SWUFormatAllowsPublicQueue('metapremier', true) === true, 'metapremier queues publicly');
} else {
    $check(SWUFormatAllowsPublicQueue('metapremier', true) === false, 'FEATFLAG_GLICKO2 off: metapremier does not queue');
}
$check(!in_array('metapremier', SWUDetectFormatOrder(), true), 'deck detection never yields metapremier');
$check(SWUStatsFormatFor('metapremier') === 'premier' && SWUStatsFormatFor('eternal') === 'eternal', 'stats id mapping');
// swustats' public APIs whitelist on SWUStatsFormats(); rated games are submitted as premier, so the
// whitelist must stay exactly what it was (spec §2.4).
$check(!in_array('metapremier', SWUStatsFormats(false), true), 'metapremier is not a stats format (public API whitelist unchanged)');

// Byte-identity for every other format: the three new keys carry their defaults.
foreach (array_keys(SWUFormatDefinitions()) as $id) {
    if ($id === 'metapremier') continue;
    $f = SWUGetFormat($id);
    $check($f['rated'] === false && $f['queueOnly'] === false && $f['queueTypes'] === null, "$id keeps defaults");
}

// Menu tree: the pool exists under PvP with its flags; Premier's pool flags are permissive.
$pvp = null;
foreach (SWUMenuTree() as $gt) foreach ($gt['options'] as $o) if ($o['id'] === 'pvp') $pvp = $o;
$pools = []; foreach ($pvp['pools'] as $p) $pools[$p['format']] = $p;
if (!$glickoOn) {   // FEATFLAG_GLICKO2 off: no pool anywhere in the menu; the pool checks resume when it turns on
    $check(!isset($pools['metapremier']), 'FEATFLAG_GLICKO2 off: PvP offers no Meta Premier pool');
} else {
$check(isset($pools['metapremier']) && $pools['metapremier']['label'] === 'Meta Premier (Bo3)', 'PvP has a "Meta Premier (Bo3)" pool');
$check((array_keys($pools)[2] ?? '') === 'metapremier', 'Meta Premier is the THIRD PvP pool (owner, 2026-10-04)', implode(',', array_keys($pools)));
$check(($pools['metapremier']['allowedQueueTypes'] ?? null) === ['bo3'], 'pool lists bo3 only');
$check(($pools['metapremier']['queueOnly'] ?? null) === true && ($pools['metapremier']['requiresLogin'] ?? null) === true, 'pool flags');
}   // FEATFLAG_GLICKO2
$check(($pools['premier']['allowedQueueTypes'] ?? null) === ['bo1', 'bo3'] && ($pools['premier']['requiresLogin'] ?? null) === false, 'premier pool flags permissive');
// Arenabot shares the Constructed pools; a rated pool has no place there (bot games are never rated).
foreach (SWUMenuTree() as $gt) foreach ($gt['options'] as $o) if ($o['id'] === 'arenabot') {
    $check(!in_array('metapremier', array_map(fn($p) => $p['format'], $o['pools']), true), 'Arenabot does not offer Meta Premier');
}

// swustats.net SubmitGameResult payload (external contract, spec §2.4): a Meta Premier game is reported as premier.
require_once './SWUSim/StatsSubmit.php';
$pl = SWUBuildGameResultPayload(['format' => 'metapremier', 'players' => ['1' => [], '2' => []]],
                                ['winner' => 1, 'gameName' => 'g', 'gameNumber' => 1, 'detail' => []]);
$check($pl['format'] === 'premier', 'swustats payload reports metapremier as premier');
$pl2 = SWUBuildGameResultPayload(['format' => 'eternal', 'players' => ['1' => [], '2' => []]],
                                 ['winner' => 1, 'gameName' => 'g', 'gameNumber' => 1, 'detail' => []]);
$check($pl2['format'] === 'eternal', 'other formats pass through to swustats unchanged');

echo $fails === 0 ? "ALL PASS\n" : "$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
