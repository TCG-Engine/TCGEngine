<?php
// SWU-PGN writer ledger: card refs (§6.1) and the copy ids that follow each physical card through the game.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_ledger.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/GeneratedCode/GeneratedCardDictionaries.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnCardMap.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/_include.php';

// Card refs.
SwuPgnTestEq(SwuPgnCardRef('SOR_108'), 'SOR#108', '§6.1: a printed card is {set}#{number}, the number zero-padded to 3 digits');
SwuPgnTestEq(SwuPgnCardRef('SOR_T02'), 'TOKEN:shield#8752877738', 'a token is TOKEN:<name>#<official id>');
SwuPgnTestEq(SwuPgnCardRef('LAW_T01'), 'TOKEN:credit#' . GetCardUUID('LAW_T01'), 'the Credit token uses the reserved name');
SwuPgnTestEq(SwuPgnCardRef('LOF_T03'), 'TOKEN:the-force#' . GetCardUUID('LOF_T03'), 'the Force token uses the reserved name');
// §6.1 [W-40]: the token name derivation (NFKD, drop marks, lower, keep a–z 0–9 space, trim, spaces → "-").
SwuPgnTestEq(array_map('SwuPgnTokenName', ['X-Wing', 'TIE Fighter', 'The Force', '  Battle   Droid ', 'Clone Trooper', 'Pâdmé\'s Ally']),
    ['xwing', 'tie-fighter', 'the-force', 'battle-droid', 'clone-trooper', 'padmes-ally'], '§6.1 token names: X-Wing → xwing, TIE Fighter → tie-fighter, …');
SwuPgnTestEq(SwuPgnCardRef('JTL_T02'), 'TOKEN:xwing#' . GetCardUUID('JTL_T02'), 'the X-Wing token is TOKEN:xwing#<id>');
global $titleData;
$all = array_keys($titleData);
$printed = array_values(array_filter($all, fn($c) => !(DecomposeCardID($c)['isToken'] ?? true)));
$step = max(1, intdiv(count($printed), 50));
$bad = [];
for ($i = 0, $n = 0; $i < count($printed) && $n < 50; $i += $step, $n++) {
    if (SwuPgnMapCardId(SwuPgnCardRef($printed[$i])) !== $printed[$i]) $bad[] = $printed[$i];
}
SwuPgnTestCheck($n === 50 && $bad === [], 'SwuPgnMapCardId inverts SwuPgnCardRef for 50 real printed cards', implode(',', $bad));
foreach (['SOR_T01', 'SOR_T02', 'LAW_T01', 'ASH_T02'] as $t) {
    SwuPgnTestEq(SwuPgnMapCardId(SwuPgnCardRef($t)), $t, "the token ref for $t maps back to it");
}

// Copy ids are numbered in deck order, per base ref, unique across both seats.
$L = SwuPgnLedgerInit([1 => ['SOR_108', 'SOR_095', 'SOR_108'], 2 => ['SOR_108']]);
SwuPgnTestEq($L['zones'][1]['deck'], ['SOR#108', 'SOR#095', 'SOR#108:2'], 'seat 1 deck copies in deck order');
SwuPgnTestEq($L['zones'][2]['deck'], ['SOR#108:3'], 'seat 2 continues the numbering (ids are unique in the game)');
SwuPgnTestEq(json_decode(json_encode($L), true), $L, 'the ledger is JSON-plain');

// Take returns the lowest-numbered copy in that zone.
$L2 = SwuPgnLedgerInit([1 => ['SOR_108', 'SOR_108', 'SOR_108']]);
$second = SwuPgnLedgerTake($L2, 1, 'deck', 'SOR_108');
SwuPgnTestEq($L2['zones'][1]['deck'], ['SOR#108:2', 'SOR#108:3'], 'copy 1 left the zone');
$L2['zones'][1]['deck'] = ['SOR#108:3', 'SOR#108:2'];       // a shuffle reorders the zone
SwuPgnTestEq(SwuPgnLedgerTake($L2, 1, 'deck', 'SOR_108'), 'SOR#108:2', 'Take picks the lowest copy, not the first in zone order');
SwuPgnTestEq($second, 'SOR#108', 'the first Take got copy 1');
SwuPgnTestEq($L2['zones'][1]['deck'], ['SOR#108:3'], 'Take removes the copy from the zone');
SwuPgnTestEq(SwuPgnLedgerTake($L2, 1, 'hand', 'SOR_108'), null, 'Take from a zone without the card is null');
SwuPgnTestEq(SwuPgnLedgerTake($L2, 2, 'deck', 'SOR_108'), null, 'Take from a seat without the zone is null');

// A unit bound by UID keeps its copy id across a take-control.
$L3 = SwuPgnLedgerInit([1 => ['SOR_095', 'SOR_095']]);
$a = SwuPgnLedgerTake($L3, 1, 'deck', 'SOR_095');
$b = SwuPgnLedgerTake($L3, 1, 'deck', 'SOR_095');
SwuPgnLedgerPut($L3, 1, 'ground', $a, 11);
SwuPgnLedgerPut($L3, 1, 'ground', $b, 12);
SwuPgnTestEq(SwuPgnLedgerByUid($L3, 12), 'SOR#095:2', 'ByUid finds the bound copy');
$moved = SwuPgnLedgerTake($L3, 1, 'ground', 'SOR_095', 12);
SwuPgnTestEq($moved, 'SOR#095:2', 'Take by UID returns that unit, not the lowest copy');
SwuPgnLedgerPut($L3, 2, 'ground', $moved, 12);
SwuPgnTestEq(SwuPgnLedgerByUid($L3, 12), 'SOR#095:2', 'the copy id survives the change of controller');
SwuPgnTestEq([$L3['zones'][1]['ground'], $L3['zones'][2]['ground']], [['SOR#095'], ['SOR#095:2']], 'the copy moved seats');
SwuPgnLedgerTake($L3, 1, 'ground', 'SOR_095');
SwuPgnTestEq(SwuPgnLedgerByUid($L3, 11), null, 'a copy taken out of play drops its UID binding');
SwuPgnTestEq(SwuPgnLedgerByUid($L3, 99), null, 'an unknown UID is null');

// Tokens: the first creation is the bare ref, later ones are suffixed.
$L4 = SwuPgnLedgerInit([1 => [], 2 => []]);
SwuPgnTestEq(SwuPgnLedgerNewToken($L4, 'SOR_T02'), 'TOKEN:shield#8752877738', 'the first Shield token');
SwuPgnTestEq(SwuPgnLedgerNewToken($L4, 'SOR_T02'), 'TOKEN:shield#8752877738:2', 'the second Shield token gets :2');
SwuPgnTestEq(SwuPgnLedgerNewToken($L4, 'SOR_T01'), 'TOKEN:experience#' . GetCardUUID('SOR_T01'), 'a different token starts its own count');

// A recording that starts on a board already in play (a fixture, a late start) gives every card on it a copy id —
// an upgrade on a base included, one per seat, so mirror base upgrades are two copies, not one id twice.
$seatAt = fn(array $ups) => ['deck' => [], 'hand' => [], 'discard' => [], 'resources' => [], 'force' => false,
    'base' => ['cardID' => 'SOR_026', 'damage' => 0, 'maxHp' => 30, 'epicUsed' => false, 'captives' => [], 'upgrades' => $ups],
    'leader' => null, 'units' => []];
$La = SwuPgnLedgerInit([1 => [], 2 => []]);
SwuPgnLedgerAdopt($La, ['seats' => [1 => $seatAt(['HMW_095']), 2 => $seatAt(['HMW_095'])]]);
SwuPgnTestEq([$La['zones'][1]['base'] ?? null, $La['zones'][2]['base'] ?? null], [['HMW#095'], ['HMW#095:2']], 'adopting a board gives each base upgrade its own copy id');

SwuPgnTestFinish();
