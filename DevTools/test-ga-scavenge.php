<?php
// Run with: php DevTools/test-ga-scavenge.php
chdir(dirname(__DIR__));
require 'Core/EngineActionRunner.php';
EngineLoadRootRuntime('GrandArchiveSim');
$gameName = 'ga_scavenge_' . getmypid();
$gameDir = 'GrandArchiveSim/Games/' . $gameName;
RegressionEnsureDir($gameDir);
$checks = 0;
function scCheck($ok, $label) {
    global $checks;
    if (!$ok) throw new RuntimeException($label);
    ++$checks;
    echo "PASS $label\n";
}
function scLoad($ids, $seat = 1) {
    global $gameDir, $playerID, $scCallback;
    $playerID = 1;
    copy('DevTools/ga-fixtures/base-states/slipstream-target-cost-ordinary.txt', $gameDir . '/Gamestate.txt');
    ParseGamestate('./GrandArchiveSim/');
    $playerID = 1;
    foreach ([1, 2] as $p) {
        $q = &GetDecisionQueue($p); $q = [];
        $hand = &GetHand($p); $hand = [];
        $temp = &GetTempZone($p); $temp = [];
    }
    $deck = &GetDeck($seat); $deck = [];
    foreach ($ids as $id) AddDeck($seat, CardID: $id);
    SetFlashMessage('');
    $scCallback = [];
}
function scIDs($zone) {
    return array_values(array_map(fn($o) => $o->CardID, array_filter($zone, fn($o) => !$o->removed)));
}
function scCallback($player, $cardID) {
    global $scCallback;
    $scCallback[] = [$player, $cardID, scIDs(GetHand($player)), scIDs(GetDeck($player))];
}
try {
    $a = 'zHhOcG9MfK'; // Hemoflux Drain, spell
    $b = 'NXGaB1dYwL'; // another Aenean spell
    $c = 'gqyWZXpxl9';
    $ally = 'em6eEh9q8y'; // Dungeon Guide
    $otherAlly = 'px60u5n1do'; // Fluffy Shopkeep
    scCheck(!ScavengeCardMatches($a, 'type', 'ALLY') && ScavengeCardMatches($ally, 'type', 'ALLY')
        && ScavengeCardMatches($otherAlly, 'type', 'ALLY'), 'test deck has nonmatches and two distinct matches');

    scLoad([$a, $b, $ally, $otherAlly, $c]);
    AddTempZone(1, CardID: $c);
    mt_srand(7);
    ScavengeForType(1, 5, 'ALLY', 'scCallback');
    scCheck(scIDs(GetHand(1)) === [$ally], 'automatically takes the first match, never the later match');
    $deck = scIDs(GetDeck(1));
    scCheck(array_slice($deck, 0, 2) === [$otherAlly, $c], 'unrevealed cards remain on top in their original order');
    $bottom = array_slice($deck, 2); sort($bottom); $expected = [$a, $b]; sort($expected);
    scCheck($bottom === $expected, 'only revealed misses go to the bottom without loss or duplication');
    scCheck(GetFlashMessage() === "REVEAL:$a|$b|$ally", 'reveal stops at the first match');
    scCheck(GetDecisionQueue(1) === [], 'no selection or rearrange prompt is queued');
    scCheck(scIDs(GetTempZone(1)) === [$c], 'scavenge does not overwrite another effect temporary zone');
    scCheck($scCallback === [[1, $ally, [$ally], $deck]], 'callback runs once after the card reaches hand and misses reach the bottom');

    scLoad([$a, $b, $c, $ally]);
    ScavengeForType(1, 3, 'ALLY', 'scCallback');
    scCheck(scIDs(GetHand(1)) === [] && $scCallback === [], 'no match within the limit takes nothing and calls no callback');
    scCheck(scIDs(GetDeck(1))[0] === $ally && count(GetDeck(1)) === 4, 'match just beyond the limit stays on top');
    scCheck(GetFlashMessage() === "REVEAL:$a|$b|$c" && GetDecisionQueue(1) === [], 'no-match reveal is capped and finishes without prompts');

    scLoad([$ally, $a]);
    ScavengeForType(1, 1, 'ALLY');
    scCheck(scIDs(GetHand(1)) === [$ally] && scIDs(GetDeck(1)) === [$a], 'match on the first card reveals and removes only that card');
    scLoad([$a, $ally]);
    ScavengeForType(1, 2, 'ALLY');
    scCheck(scIDs(GetHand(1)) === [$ally] && scIDs(GetDeck(1)) === [$a], 'match exactly at the limit is taken');

    scLoad([$a, $b]);
    ScavengeForType(1, 10, 'ALLY');
    $ids = scIDs(GetDeck(1)); sort($ids);
    scCheck($ids === $expected && GetFlashMessage() === "REVEAL:$a|$b", 'short deck reveals each available card only once');
    foreach ([0, -1] as $limit) {
        scLoad([$a, $ally]);
        ScavengeForType(1, $limit, 'ALLY', 'scCallback');
        scCheck(scIDs(GetDeck(1)) === [$a, $ally] && GetFlashMessage() === '' && $scCallback === [], "limit $limit does nothing");
    }
    scLoad([]);
    ScavengeForType(1, 6, 'ALLY', 'scCallback');
    scCheck(GetFlashMessage() === '' && $scCallback === [] && GetDecisionQueue(1) === [], 'empty deck completes without prompts or callback');

    scLoad([$a, $ally, $b, $otherAlly, $c], 2);
    ScavengeForType(2, 5, 'ALLY');
    ScavengeForType(2, 5, 'ALLY');
    scCheck(scIDs(GetHand(2)) === [$ally, $otherAlly] && scIDs(GetHand(1)) === [], 'consecutive scavenges work for the other player perspective');
    scCheck(scIDs(GetDeck(2)) === [$c, $a, $b], 'second scavenge starts immediately below the first match');
    scCheck(GetFlashMessage() === "REVEAL:$a|$ally|$b|$otherAlly", 'consecutive reveals accumulate without revealing deeper cards');

    scLoad([$otherAlly, $a, $ally]);
    GetDeck(1)[0]->removed = true;
    ScavengeForType(1, 2, 'ALLY');
    scCheck(scIDs(GetHand(1)) === [$ally] && GetFlashMessage() === "REVEAL:$a|$ally", 'removed deck entries neither match nor consume the reveal limit');

    $orders = [];
    for ($seed = 0; $seed < 12; ++$seed) {
        scLoad([$a, $b, $c, $ally]);
        mt_srand($seed);
        ScavengeForType(1, 4, 'ALLY');
        $orders[] = implode(',', scIDs(GetDeck(1)));
    }
    scCheck(count(array_unique($orders)) > 1, 'different random seeds produce different bottom orders');
    scLoad([$a, $b, $c, $ally]);
    mt_srand(0);
    ScavengeForType(1, 4, 'ALLY');
    scCheck(implode(',', scIDs(GetDeck(1))) === $orders[0], 'replaying with the same random seed preserves the bottom order');
    echo "OK $checks checks\n";
} finally {
    foreach (glob($gameDir . '/*') as $f) @unlink($f);
    @rmdir($gameDir);
}
