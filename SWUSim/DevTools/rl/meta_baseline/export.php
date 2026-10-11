<?php
// Meta-baseline EXPORT (skill `swusim-ash-meta-bot-run`): the melee tournaments imported into the local SWUStats DB, restricted to one
// tournament set, as JSON — every deck with its melee DecklistId (sourceID) and ARCHETYPE key, every matchup row, archetype labels, and
// the curated fixtures' archetype keys (whose '# Style:' labels convert.php reuses). Runs in the SWUDeck container (needs its dictionaries):
//   docker cp <events.txt> otmtcge-swudeck-web-server-1:/tmp/events.txt
//   docker exec otmtcge-swudeck-web-server-1 php -d memory_limit=1G /var/www/html/TCGEngine/SWUSim/DevTools/rl/meta_baseline/export.php \
//     /tmp/events.txt SWUSim/Tests/BotFixtures/ash-meta-2026-09 > export.json
// events.txt = one melee tournament id (meleetournament.tournamentLink) per line. Derived from SWUSim/DevTools/rl/melee_archetype_export.php.
$EVENTS = array_map('intval', array_filter(array_map('trim', file($argv[1] ?? '/tmp/events.txt'))));
$FIXDIR = $argv[2] ?? 'SWUSim/Tests/BotFixtures/ash-meta-2026-09';
// Export the melee tournaments imported into the SWUStats DB (meleetournament / meleetournamentdeck /
// meleetournamentmatchup — Stats/MeleeTournamentParser.php) as JSON, every deck keyed by ARCHETYPE = canonical leader
// (reprint → earliest printing; two printings stay distinct) + StatsBaseBucket(base): common bases collapse to
// colour + type (30HP / Force / Splash), rare bases stay by name. The bot fixtures in ash-meta-2026-09/ are keyed the same
// way so the two line up. Read-only. Runs in the SWUDeck container (it needs SWUDeck's generated dictionaries):
//   docker exec otmtcge-swudeck-web-server-1 php -d memory_limit=1G \
//     /var/www/html/TCGEngine/SWUSim/DevTools/rl/melee_archetype_export.php > meta_export.json
// then SWUSim/DevTools/rl/real_matchup_matrix.py.
chdir('/var/www/html/TCGEngine');
include_once './SWUDeck/GeneratedCode/GeneratedCardDictionaries.php';
include_once './AppCore/SWU/Overrides.php';
include_once './Core/StatsBaseRegistry.php';
include_once './Database/ConnectionManager.php';
$canon = fn($id) => function_exists('CardIDOverride') ? CardIDOverride((string)$id) : (string)$id;
function archKey($leader, $base, $canon) {
    if ($leader === null || $leader === '' || $base === null || $base === '') return null;
    $b = StatsBaseBucket($base);
    return $canon($leader) . '|' . $b['key'];
}
function archLabel($key) {
    [$l, $b] = explode('|', $key, 2);
    $set = explode('_', $l)[0];
    $lt = CardTitle($l) . " ($set)";
    if (str_starts_with($b, 'grp:')) { [, $type, $col] = explode(':', $b); $bl = $col . ' ' . ($type === 'Standard' ? '30HP' : $type); }
    else $bl = CardTitle($b);
    return "$lt · $bl";
}
$conn = GetLocalMySQLConnection();
$decks = [];
foreach ($conn->query('SELECT d.deckID, d.tournamentID, t.tournamentLink, d.player, d.leader, d.base, d.`rank`, d.sourceID FROM meleetournamentdeck d JOIN meleetournament t ON t.tournamentID = d.tournamentID') as $r) {
    $k = archKey($r['leader'], $r['base'], $canon);
    if (!in_array((int)$r['tournamentLink'], $EVENTS, true)) continue;
    $decks[$r['deckID']] = ['t' => (int)$r['tournamentLink'], 'k' => $k, 'src' => $r['sourceID'], 'p' => $r['player'], 'L' => $r['leader'], 'B' => $r['base']];
}
$m = [];
foreach ($conn->query('SELECT player, opponent, wins, losses, draws FROM meleetournamentmatchup') as $r) {
    if (!isset($decks[(int)$r['player']])) continue;   // another tournament set's match
    $m[] = [(int)$r['player'], $r['opponent'] === null ? null : (int)$r['opponent'], (int)$r['wins'], (int)$r['losses'], (int)$r['draws']];
}
$labels = [];
foreach ($decks as $d) if ($d['k'] !== null && !isset($labels[$d['k']])) $labels[$d['k']] = archLabel($d['k']);
$fixtures = [];
foreach (glob('./' . $FIXDIR . '/*.txt') as $f) {
    $sec = ''; $L = ''; $B = '';
    foreach (file($f, FILE_IGNORE_NEW_LINES) as $l) {
        $l = trim($l);
        if (in_array($l, ['Leader', 'Base', 'Deck', 'Sideboard'], true)) { $sec = $l; continue; }
        if (preg_match('/^\d+\s+(\S+)/', $l, $mm)) { if ($sec === 'Leader') $L = $mm[1]; elseif ($sec === 'Base') $B = $mm[1]; }
    }
    $k = archKey($L, $B, $canon); $fixtures[basename($f, '.txt')] = $k;
    if ($k && !isset($labels[$k])) $labels[$k] = archLabel($k);
}
echo json_encode(['decks' => $decks, 'matchups' => $m, 'labels' => $labels, 'fixtures' => $fixtures]);
