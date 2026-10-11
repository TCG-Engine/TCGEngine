<?php
// Core/PopupZoneVisibility.php across every sim's real schema: games follow the schema's visibility by owning
// seat; deckbuilders keep the historical rule (a shared deck view must stay readable).
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_popup_zone_visibility.php
require_once __DIR__ . '/../../Core/PopupZoneVisibility.php';
$fails = 0;
$check = function ($ok, $msg) use (&$fails) { echo ($ok ? 'PASS' : 'FAIL') . ": $msg\n"; if (!$ok) $fails++; };
$seat = fn(int $n) => ['viewerID' => (string)$n, 'viewerSeat' => $n, 'isSpectator' => false];
$spec = ['viewerID' => 'S', 'viewerSeat' => null, 'isSpectator' => true];
$may = fn(string $root, string $zone, array $v, int $persp = 1, bool $casterHands = false) => PopupMayShowZone($root, $zone, $v, $persp, $casterHands);

// Which schemas count as games (hidden decks) — derived, so a new sim is covered without editing this test.
$games = $decks = [];
foreach (glob(__DIR__ . '/../../Schemas/*/GameSchema.txt') as $f) {
    $root = basename(dirname($f));
    ((PopupZoneVisibilityMap($root)['Deck'] ?? '') === 'Private' ? $games[] = $root : $decks[] = $root);
}
$check(in_array('SWUSim', $games, true) && in_array('GrandArchiveSim', $games, true) && in_array('SWUDeck', $decks, true),
    'SWUSim and GrandArchiveSim read as games, SWUDeck as a deckbuilder (games: ' . implode(',', $games) . ')');

foreach ($games as $root) {
    $check(!$may($root, 'p2Hand', $seat(1)) && !$may($root, 'theirHand', $seat(1)), "$root: seat 1 cannot read seat 2's hand by any name");
    $check($may($root, 'myHand', $seat(1)) && $may($root, 'p1Hand', $seat(1)), "$root: seat 1 reads its own hand");
    $check(!$may($root, 'myDeck', $seat(1)) && !$may($root, 'p2Deck', $seat(1)) && !$may($root, 'p1Deck', $spec), "$root: no one reads a deck");
    $check(!$may($root, 'myVersions', $seat(1)) && !$may($root, 'p1Versions', $seat(1)), "$root: no one reads the undo stack");
    $check(!$may($root, 'p1Hand', $spec) && $may($root, 'p1Hand', $spec, 1, true), "$root: a spectator sees hands only in caster mode");
}
$check($may('SWUSim', 'p2Discard', $seat(1)) && $may('SWUSim', 'theirLeader', $spec), 'SWUSim: public zones stay public');
$check(!$may('SWUSim', 'p2Resources', $seat(1)) && $may('SWUSim', 'myResources', $seat(1)), 'SWUSim: resources are the owner\'s');
$check(!$may('SWUSim', 'p3Hand', $seat(1)) && $may('SWUSim', 'p3Hand', $seat(3), 3), 'SWUSim (Twin Suns): seat 3\'s hand is seat 3\'s only');
$check(!$may('GrandArchiveSim', 'p2Memory', $seat(1)) && $may('GrandArchiveSim', 'myMemory', $seat(1)) && $may('GrandArchiveSim', 'theirGraveyard', $seat(1)),
    "GrandArchiveSim: an opponent's Memory is hidden under any name, graveyards are public");
$check($may('FaBSim', 'theirArsenal', $seat(1)), 'FaBSim: the arsenal is face up, as the board sends it');
$check(!$may('HellbreakSim', 'theirVault', $seat(1)), 'HellbreakSim: a Private vault stays hidden');

// Deckbuilders: the historical rule, byte for byte.
foreach ($decks as $root) {
    $check($may($root, 'myMainDeck', $seat(1)) && $may($root, 'MainDeck', $spec) && $may($root, 'myVersions', $seat(1)),
        "$root: deck views stay readable (own, shared, versions)");
}

echo $fails === 0 ? "\nALL PASS\n" : "\n$fails FAILED\n";
exit($fails === 0 ? 0 : 1);
