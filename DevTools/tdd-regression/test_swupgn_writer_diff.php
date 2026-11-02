<?php
// SWU-PGN writer diff: two board snapshots → the list of changes between them. Pure data, no engine.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_writer_diff.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';
require_once __DIR__ . '/../../SWUSim/Custom/SwuPgnWriter/_include.php';

function DiffSeat(array $over = []): array {
    return array_replace([
        'deck' => ['SOR_101', 'SOR_102'], 'hand' => ['SOR_095'], 'discard' => [],
        'resources' => [['cardID' => 'SOR_103', 'ready' => true, 'credit' => false], ['cardID' => 'SOR_104', 'ready' => true, 'credit' => false]],
        'force' => false,
        'base' => ['cardID' => 'SOR_026', 'damage' => 0, 'maxHp' => 30, 'epicUsed' => false, 'captives' => []],
        'leader' => ['cardID' => 'SOR_005', 'deployed' => false, 'ready' => true, 'epicUsed' => false, 'deployedUid' => null],
        'units' => [],
    ], $over);
}
function DiffUnit(string $cardID, array $over = []): array {
    return array_replace([
        'cardID' => $cardID, 'arena' => 'ground', 'damage' => 0, 'ready' => true, 'owner' => 1, 'controller' => 1,
        'power' => 3, 'hp' => 3, 'keywords' => [], 'upgrades' => [],
        'tokens' => ['shield' => 0, 'experience' => 0, 'advantage' => 0, 'weakness' => 0], 'captives' => [],
    ], $over);
}
function DiffSnap(array $s1 = [], array $s2 = [], array $root = []): array {
    return array_replace(['round' => 1, 'phase' => 'MAIN', 'initiative' => 1, 'initiativeTaken' => false, 'active' => 1,
        'seats' => [1 => DiffSeat($s1), 2 => DiffSeat(array_replace(['hand' => ['SOR_096']], $s2))]], $root);
}
$res = fn(string $id, bool $ready = true, bool $credit = false) => ['cardID' => $id, 'ready' => $ready, 'credit' => $credit];
$restRes = [$res('SOR_103', false), $res('SOR_104', false)];

$same = DiffSnap();
SwuPgnTestEq(SwuPgnDiff($same, $same), [], 'no change → no changes');

// A unit played from hand, paying 2 resources.
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap(['hand' => [], 'resources' => $restRes, 'units' => [7 => DiffUnit('SOR_095')]])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'ground', 'uid' => 7, 'exhausted' => false],
    ['k' => 'resources', 'seat' => 1, 'exhaustedDelta' => 2],
    ['k' => 'stats', 'seat' => 1, 'uid' => 7, 'power' => 3, 'hp' => 3, 'keywords' => []],
], 'hand → ground play with 2 resources exhausting');

// An exhausted unit entering play is flagged.
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_095', ['ready' => false])]])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'ground', 'uid' => 7, 'exhausted' => true],
    ['k' => 'stats', 'seat' => 1, 'uid' => 7, 'power' => 3, 'hp' => 3, 'keywords' => []],
], 'a unit that enters exhausted carries exhausted: true');

// A defeat: ground → discard.
$onBoard = ['hand' => [], 'units' => [7 => DiffUnit('SOR_095', ['damage' => 1])]];
SwuPgnTestEq(SwuPgnDiff(DiffSnap($onBoard), DiffSnap(['hand' => [], 'discard' => ['SOR_095']])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'ground', 'to' => 'discard', 'uid' => 7],
], 'a defeat moves the unit to its owner\'s discard');

// UID matching: two copies on the ground, the SECOND one is defeated.
$twins = ['hand' => [], 'units' => [7 => DiffUnit('SOR_095'), 8 => DiffUnit('SOR_095')]];
SwuPgnTestEq(SwuPgnDiff(DiffSnap($twins), DiffSnap(['hand' => [], 'discard' => ['SOR_095'], 'units' => [7 => DiffUnit('SOR_095')]])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'ground', 'to' => 'discard', 'uid' => 8],
], 'the defeated copy is identified by UID, not by card');

// A stolen unit defeated goes to its OWNER's discard.
$stolen = DiffSnap([], ['units' => [9 => DiffUnit('SOR_095', ['owner' => 1, 'controller' => 2])]]);
SwuPgnTestEq(SwuPgnDiff($stolen, DiffSnap(['discard' => ['SOR_095']])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'ground', 'to' => 'discard', 'uid' => 9, 'fromSeat' => 2, 'toSeat' => 1],
], 'a defeated stolen unit lands in the owner\'s discard (seat = owner; it crossed seats)');

// Multiset matching: a draw while the deck holds a duplicate, and the deck is reordered (a shuffle) — one move only.
$dupDeck = ['deck' => ['SOR_101', 'SOR_102', 'SOR_101']];
SwuPgnTestEq(SwuPgnDiff(DiffSnap($dupDeck), DiffSnap(['deck' => ['SOR_101', 'SOR_101'], 'hand' => ['SOR_095', 'SOR_102']])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_102', 'from' => 'deck', 'to' => 'hand'],
], 'deck reorders are not moves; the drawn card is');
SwuPgnTestEq(SwuPgnDiff(DiffSnap($dupDeck), DiffSnap(['deck' => ['SOR_102', 'SOR_101', 'SOR_101']])), [], 'a pure shuffle is no change');
SwuPgnTestEq(SwuPgnDiff(DiffSnap($dupDeck), DiffSnap(['deck' => ['SOR_102', 'SOR_101'], 'hand' => ['SOR_095', 'SOR_101']])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_101', 'from' => 'deck', 'to' => 'hand'],
], 'one of two duplicate copies drawn is one move (counted, not membership)');

// Resourcing a card from hand (enters ready).
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap(['hand' => [], 'resources' => [$res('SOR_103'), $res('SOR_104'), $res('SOR_095')]])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'resource', 'exhausted' => false],
], 'hand → resource');

// A Shield pops.
$shielded = ['hand' => [], 'units' => [7 => DiffUnit('SOR_095', ['tokens' => ['shield' => 1, 'experience' => 0, 'advantage' => 0, 'weakness' => 0]])]];
SwuPgnTestEq(SwuPgnDiff(DiffSnap($shielded), DiffSnap($onBoard = ['hand' => [], 'units' => [7 => DiffUnit('SOR_095')]])), [
    ['k' => 'token', 'seat' => 1, 'uid' => 7, 'kind' => 'shield', 'delta' => -1],
], 'a shield pop is a token delta of -1');

// A unit that enters play with a token (Shielded) reports the token too.
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap($shielded)), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'ground', 'uid' => 7, 'exhausted' => false],
    ['k' => 'token', 'seat' => 1, 'uid' => 7, 'kind' => 'shield', 'delta' => 1],
    ['k' => 'stats', 'seat' => 1, 'uid' => 7, 'power' => 3, 'hp' => 3, 'keywords' => []],
], 'a token on a unit entering play');

// A Credit spent; credits are never resource moves.
$credits = ['resources' => [$res('SOR_103'), $res('SOR_104'), $res('LAW_T01', true, true), $res('LAW_T01', true, true)]];
SwuPgnTestEq(SwuPgnDiff(DiffSnap($credits), DiffSnap(['resources' => [$res('SOR_103'), $res('SOR_104'), $res('LAW_T01', true, true)]])), [
    ['k' => 'credit', 'seat' => 1, 'delta' => -1],
], 'a spent Credit');

// The Force gained.
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap([], ['force' => true])), [
    ['k' => 'force', 'seat' => 2, 'has' => true],
], 'the Force gained');

// A capture, then its rescue.
$guard = DiffUnit('SOR_049', ['owner' => 2, 'controller' => 2]);
$preCap = DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_095')]], ['units' => [20 => $guard]]);
$captured = DiffSnap(['hand' => []], ['units' => [20 => array_replace($guard, ['captives' => ['SOR_095']])]]);
SwuPgnTestEq(SwuPgnDiff($preCap, $captured), [
    ['k' => 'capture', 'seat' => 2, 'cardID' => 'SOR_095', 'uid' => 7, 'captor' => 20, 'from' => 'ground'],
], 'a capture names the captured UID and its captor');
$rescued = DiffSnap(['hand' => [], 'units' => [31 => DiffUnit('SOR_095', ['ready' => false])]], ['units' => [20 => $guard]]);
SwuPgnTestEq(SwuPgnDiff($captured, $rescued), [
    ['k' => 'rescue', 'seat' => 1, 'cardID' => 'SOR_095', 'captor' => 20, 'uid' => 31, 'to' => 'ground', 'exhausted' => true],
    ['k' => 'stats', 'seat' => 1, 'uid' => 31, 'power' => 3, 'hp' => 3, 'keywords' => []],
], 'a rescue names the captor and the new UID; seat = the owner it returns to');
$baseCap = DiffSnap(['hand' => []], ['base' => array_replace(DiffSeat()['base'], ['captives' => ['SOR_095']])]);
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_095')]]), $baseCap), [
    ['k' => 'capture', 'seat' => 2, 'cardID' => 'SOR_095', 'uid' => 7, 'captor' => 'base@2', 'from' => 'ground'],
], 'a base captive names base@N as captor');

// Take control: same UID, new controller.
$mine = DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_095')]]);
$theirs = DiffSnap(['hand' => []], ['units' => [7 => DiffUnit('SOR_095', ['controller' => 2])]]);
SwuPgnTestEq(SwuPgnDiff($mine, $theirs), [
    ['k' => 'control', 'seat' => 2, 'uid' => 7, 'cardID' => 'SOR_095', 'from' => 1, 'to' => 2],
], 'a take control keeps the UID and is not a move');

// Base damage and a heal.
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap([], ['base' => array_replace(DiffSeat()['base'], ['damage' => 4])])), [
    ['k' => 'damage', 'seat' => 2, 'target' => 'base@2', 'amount' => 4, 'hp' => 26],
], 'base damage with the HP left');
$hurt = ['hand' => [], 'units' => [7 => DiffUnit('SOR_095', ['damage' => 2])]];
SwuPgnTestEq(SwuPgnDiff(DiffSnap($hurt), DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_095', ['damage' => 1])]])), [
    ['k' => 'heal', 'seat' => 1, 'target' => 7, 'amount' => 1, 'hp' => 2],
], 'a unit heal with the HP left');

// Exhaust (an attack) and a stats-only buff.
SwuPgnTestEq(SwuPgnDiff(DiffSnap($onBoard), DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_095', ['ready' => false])]])), [
    ['k' => 'exhaust', 'seat' => 1, 'target' => 7, 'cardID' => 'SOR_095'],
], 'a unit exhausts');
SwuPgnTestEq(SwuPgnDiff(DiffSnap($onBoard), DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_095', ['power' => 5, 'keywords' => ['raid 2']])]])), [
    ['k' => 'stats', 'seat' => 1, 'uid' => 7, 'power' => 5, 'hp' => 3, 'keywords' => ['raid 2']],
], 'a stats-only buff');

// A printed upgrade played onto a unit, and a pilot.
$withUp = ['units' => [7 => DiffUnit('SOR_049', ['upgrades' => [['cardID' => 'SOR_095', 'uid' => null, 'isPilot' => false]]])]];
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['units' => [7 => DiffUnit('SOR_049')]]), DiffSnap(array_replace($withUp, ['hand' => []]))), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'hand', 'to' => 'ground', 'host' => 7, 'kind' => 'upgrade'],
], 'an upgrade played from hand names its host');
SwuPgnTestEq(SwuPgnDiff(DiffSnap($withUp), DiffSnap(['discard' => ['SOR_095'], 'units' => [7 => DiffUnit('SOR_049')]])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_095', 'from' => 'ground', 'to' => 'discard', 'host' => 7, 'kind' => 'upgrade'],
], 'a defeated upgrade leaves its host');

// A leader deploys (as a unit), and is later defeated back to base.
$deployed = DiffSnap(['leader' => ['cardID' => 'SOR_005', 'deployed' => true, 'ready' => true, 'epicUsed' => true, 'deployedUid' => 12],
    'units' => [12 => DiffUnit('SOR_005', ['power' => 5, 'hp' => 6])]]);
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), $deployed), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'base', 'to' => 'ground', 'uid' => 12, 'exhausted' => false],
    ['k' => 'stats', 'seat' => 1, 'uid' => 12, 'power' => 5, 'hp' => 6, 'keywords' => []],
    ['k' => 'leader', 'seat' => 1, 'field' => 'deployed', 'value' => true],
    ['k' => 'leader', 'seat' => 1, 'field' => 'epicUsed', 'value' => true],
], 'a deploy is a base → ground move plus the leader flags');
$home = DiffSnap(['leader' => ['cardID' => 'SOR_005', 'deployed' => false, 'ready' => false, 'epicUsed' => true, 'deployedUid' => null]]);
SwuPgnTestEq(SwuPgnDiff($deployed, $home), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_005', 'from' => 'ground', 'to' => 'base', 'uid' => 12, 'exhausted' => true],
    ['k' => 'leader', 'seat' => 1, 'field' => 'deployed', 'value' => false],
], 'a defeated leader moves home, exhausted');
// A pilot ejected to a unit (Eject) and a unit made a pilot (L3-37): a role change. The move keeps the host it left or
// joined, and says which way the role went (detach / attachFromPlay) — in the same arena, from = to.
$hostOnly = DiffUnit('SEC_214');
$withPilot = DiffUnit('SEC_214', ['upgrades' => [['cardID' => 'JTL_046', 'uid' => null, 'isPilot' => true]]]);
$moveOf = fn(array $b, array $a) => array_values(array_filter(SwuPgnDiff(DiffSnap(['units' => $b]), DiffSnap(['units' => $a])), fn($c) => $c['k'] === 'move'));
SwuPgnTestEq($moveOf([7 => $withPilot], [7 => $hostOnly, 9 => DiffUnit('JTL_046', ['ready' => false])]), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'ground', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'detach' => true, 'exhausted' => true],
], 'a pilot ejected to a unit: detach, bound to its new unit');
SwuPgnTestEq($moveOf([7 => $hostOnly, 9 => DiffUnit('JTL_046')], [7 => $withPilot]), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_046', 'from' => 'ground', 'to' => 'ground', 'uid' => 9, 'host' => 7, 'kind' => 'upgrade', 'attachFromPlay' => true],
], 'a unit made a pilot: attachFromPlay, from the unit it was');

// One card defeated from play, its namesakes discarded from hand and deck, a deck copy shown in staging (Annihilator):
// the unit goes to the discard pile, never into staging — units leaving play pair before the rest.
$annB = DiffSnap([], ['hand' => ['SOR_225'], 'deck' => ['SOR_225', 'SOR_225'], 'units' => [7 => DiffUnit('SOR_225', ['arena' => 'space', 'owner' => 2, 'controller' => 2])]]);
$annA = DiffSnap(['temp' => ['SOR_225']], ['hand' => [], 'deck' => [], 'discard' => ['SOR_225', 'SOR_225', 'SOR_225']]);
$annMoves = array_values(array_filter(SwuPgnDiff($annB, $annA), fn($c) => $c['k'] === 'move'));
SwuPgnTestEq(array_map(fn($c) => [$c['from'], $c['to']], array_values(array_filter($annMoves, fn($c) => isset($c['uid'])))), [['space', 'discard']],
    'a defeated unit pairs with a discard, not a staged copy');
SwuPgnTestEq(count(array_filter($annMoves, fn($c) => $c['from'] === 'deck' && $c['to'] === 'temp')), 1, 'and a deck copy is the one staged');

// An upgrade played from an EXHAUSTED resource (The Armorer): it left the row's exhausted count.
SwuPgnTestEq(array_values(array_filter(SwuPgnDiff(DiffSnap(['resources' => [$res('SOR_103', false), $res('SOR_104')], 'units' => [7 => DiffUnit('SOR_049')]]),
    DiffSnap(['resources' => [$res('SOR_104')], 'units' => [7 => DiffUnit('SOR_049', ['upgrades' => [['cardID' => 'SOR_103', 'uid' => null, 'isPilot' => false]]])]])),
    fn($c) => $c['k'] === 'move')), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_103', 'from' => 'resource', 'to' => 'ground', 'host' => 7, 'kind' => 'upgrade', 'fromExhausted' => true],
], 'an upgrade played from an exhausted resource says so');

// A base upgrade (Fortify) sits in the base area: played from hand into "base", defeated from "base".
$baseWith = fn(array $ups) => array_replace(DiffSeat()['base'], ['upgrades' => $ups]);
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['hand' => ['HMW_171'], 'base' => $baseWith([])]), DiffSnap(['hand' => [], 'base' => $baseWith(['HMW_171'])])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'HMW_171', 'from' => 'hand', 'to' => 'base'],
], 'a base upgrade played: hand → base');
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['base' => $baseWith(['HMW_171'])]), DiffSnap(['discard' => ['HMW_171'], 'base' => $baseWith([])])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'HMW_171', 'from' => 'base', 'to' => 'discard'],
], 'a base upgrade defeated: base → discard');

// A unit defeated while a namesake is drawn: the unit goes to the discard pile, the deck copy to the hand (a unit
// leaving play tries the discard pile first).
$dd = SwuPgnDiff(DiffSnap(['hand' => [], 'deck' => ['SOR_095'], 'units' => [7 => DiffUnit('SOR_095')]]), DiffSnap(['hand' => ['SOR_095'], 'deck' => [], 'discard' => ['SOR_095']]));
SwuPgnTestEq(array_map(fn($c) => [$c['from'], $c['to'], isset($c['uid'])], array_values(array_filter($dd, fn($c) => $c['k'] === 'move'))),
    [['deck', 'hand', false], ['ground', 'discard', true]], 'a defeated unit pairs with the discard pile before the hand');

// A captive lifted out of capture with nowhere to be yet (played as a pilot, next request): the move names its captor.
SwuPgnTestEq(array_values(array_filter(SwuPgnDiff(DiffSnap(['units' => [7 => DiffUnit('SOR_046', ['captives' => ['JTL_045']])]]),
    DiffSnap(['units' => [7 => DiffUnit('SOR_046')]])), fn($c) => $c['k'] === 'move')), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_045', 'from' => 'capture', 'to' => 'outsideTheGame', 'captor' => 7],
], 'a move out of capture names its captor');

// A captive rescued while the picker shows it as a staged copy (Unexpected Escape): the rescue pairs with the capture,
// the staged copy just goes — a staged card pairs only after every real place has.
$cb = DiffSnap(['temp' => ['SOR_164']], ['units' => [7 => DiffUnit('SHD_120', ['owner' => 2, 'controller' => 2, 'captives' => ['SOR_164']])]]);
$ca = DiffSnap(['units' => [9 => DiffUnit('SOR_164', ['ready' => false])]], ['units' => [7 => DiffUnit('SHD_120', ['owner' => 2, 'controller' => 2])]]);
SwuPgnTestEq(array_map(fn($c) => [$c['k'], $c['from'] ?? null, $c['to'] ?? null], array_values(array_filter(SwuPgnDiff($cb, $ca), fn($c) => in_array($c['k'], ['move', 'rescue'], true)))),
    [['rescue', null, 'ground'], ['move', 'temp', 'outsideTheGame']], 'a rescue pairs with the capture, not the picker\'s staged copy');

// Smuggled from a READY resource into play exhausted: how it left the row and how it entered play are two facts.
SwuPgnTestEq(array_values(array_filter(SwuPgnDiff(DiffSnap(), DiffSnap(['resources' => [$res('SOR_104')], 'units' => [7 => DiffUnit('SOR_103', ['ready' => false])]])), fn($c) => $c['k'] === 'move')), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_103', 'from' => 'resource', 'to' => 'ground', 'uid' => 7, 'exhausted' => true, 'fromExhausted' => false],
], 'a resource smuggled into play: fromExhausted (the row) apart from exhausted (the arrival)');
// A Pilot leader comes home exhausted too: its move off the host keeps the host (the ledger finds it there) and says so.
$piloted = DiffSnap(['leader' => ['cardID' => 'JTL_012', 'deployed' => true, 'ready' => false, 'epicUsed' => true, 'deployedUid' => null],
    'units' => [7 => DiffUnit('SOR_237', ['arena' => 'space', 'upgrades' => [['cardID' => 'JTL_012', 'uid' => null, 'isPilot' => true]]])]]);
$pilotHome = DiffSnap(['leader' => ['cardID' => 'JTL_012', 'deployed' => false, 'ready' => false, 'epicUsed' => true, 'deployedUid' => null],
    'units' => [7 => DiffUnit('SOR_237', ['arena' => 'space'])]]);
SwuPgnTestEq(array_values(array_filter(SwuPgnDiff($piloted, $pilotHome), fn($c) => $c['k'] === 'move')), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_012', 'from' => 'space', 'to' => 'base', 'host' => 7, 'kind' => 'upgrade', 'exhausted' => true],
], 'a Pilot leader moves home off its host, exhausted');
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap(['leader' => array_replace(DiffSeat()['leader'], ['ready' => false, 'epicUsed' => false])])), [
    ['k' => 'exhaust', 'seat' => 1, 'target' => 'leader@1', 'cardID' => 'SOR_005'],
], 'an undeployed leader exhausting (its action) is leader@N');
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap(['base' => array_replace(DiffSeat()['base'], ['epicUsed' => true])])), [
    ['k' => 'baseEpic', 'seat' => 1],
], 'a base Epic Action used');

// A double-sided leader turning over is a face change, never a deploy or a move.
$flipLeader = fn(bool $front) => ['cardID' => 'TWI_017', 'deployed' => false, 'ready' => false, 'epicUsed' => false, 'deployedUid' => null, 'onStartingSide' => $front];
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['leader' => $flipLeader(true)]), DiffSnap(['leader' => $flipLeader(false)])), [
    ['k' => 'leader', 'seat' => 1, 'field' => 'onStartingSide', 'value' => false],
], 'a leader flip is an onStartingSide change');

// Tokens created as units come from, and return to, outside the game.
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap(['units' => [40 => DiffUnit('JTL_T01', ['arena' => 'space'])]])), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_T01', 'from' => 'outsideTheGame', 'to' => 'space', 'uid' => 40, 'exhausted' => false],
    ['k' => 'stats', 'seat' => 1, 'uid' => 40, 'power' => 3, 'hp' => 3, 'keywords' => []],
], 'a token unit created');
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['units' => [40 => DiffUnit('JTL_T01', ['arena' => 'space'])]]), DiffSnap()), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'JTL_T01', 'from' => 'space', 'to' => 'outsideTheGame', 'uid' => 40],
], 'a token unit defeated');

// A card crossing seats says so: seat 2's discarded unit played into seat 1's ground arena.
SwuPgnTestEq(SwuPgnDiff(DiffSnap([], ['discard' => ['SOR_095']]), DiffSnap(['units' => [9 => DiffUnit('SOR_095', ['owner' => 2, 'controller' => 1])]])), [
    ['k' => 'move', 'seat' => 2, 'cardID' => 'SOR_095', 'from' => 'discard', 'to' => 'ground', 'uid' => 9, 'exhausted' => false, 'fromSeat' => 2, 'toSeat' => 1],
    ['k' => 'stats', 'seat' => 1, 'uid' => 9, 'power' => 3, 'hp' => 3, 'keywords' => []],
], 'a move between seats carries fromSeat / toSeat');

// A host defeated with an upgrade: the upgrade leaves first (the engine discards it first).
$hosted = ['hand' => [], 'units' => [7 => DiffUnit('SOR_049', ['upgrades' => [['cardID' => 'SOR_120', 'uid' => null, 'isPilot' => false]]])]];
SwuPgnTestEq(array_map(fn($c) => $c['cardID'], SwuPgnDiff(DiffSnap($hosted), DiffSnap(['hand' => [], 'discard' => ['SOR_120', 'SOR_049']]))), ['SOR_120', 'SOR_049'],
    'a leaving host\'s upgrade moves before the host');

// An upgrade moved to another host.
$two = fn(array $on7, array $on8) => DiffSnap(['hand' => [], 'units' => [7 => DiffUnit('SOR_049', ['upgrades' => $on7]), 8 => DiffUnit('SOR_046', ['upgrades' => $on8])]]);
$up = [['cardID' => 'SOR_120', 'uid' => null, 'isPilot' => false]];
SwuPgnTestEq(SwuPgnDiff($two($up, []), $two([], $up)), [
    ['k' => 'move', 'seat' => 1, 'cardID' => 'SOR_120', 'from' => 'ground', 'to' => 'ground', 'host' => 8, 'kind' => 'upgrade', 'fromHost' => 7],
], 'an upgrade moved between hosts names both hosts');

// Initiative claimed.
SwuPgnTestEq(SwuPgnDiff(DiffSnap(), DiffSnap([], [], ['initiative' => 2, 'initiativeTaken' => true])), [
    ['k' => 'initiative', 'seat' => 2, 'taken' => true],
], 'initiative claimed');

// §10.1 STATS [W-55]: a change of traits / blank / leaderUnit alone is a stats change; a unit entering states them all.
$tu = fn(array $o = []) => DiffUnit('SOR_095', array_replace(['traits' => ['rebel', 'trooper'], 'blank' => false, 'leaderUnit' => false], $o));
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['units' => [7 => $tu()]]), DiffSnap(['units' => [7 => $tu(['blank' => true])]])), [
    ['k' => 'stats', 'seat' => 1, 'uid' => 7, 'power' => 3, 'hp' => 3, 'keywords' => [], 'traits' => ['rebel', 'trooper'], 'blank' => true, 'leaderUnit' => false],
], 'blanked: a stats change carrying the traits');
SwuPgnTestEq(array_column(SwuPgnDiff(DiffSnap(['units' => [7 => $tu()]]), DiffSnap(['units' => [7 => $tu(['traits' => ['mandalorian', 'rebel', 'trooper']])]])), 'traits'),
    [['mandalorian', 'rebel', 'trooper']], 'a gained trait alone is a stats change');
// §10.1 TAKE_CONTROL, upgrade form [W-56]: an attached upgrade's controller changed on the same host.
$up = fn(int $ctl) => ['cardID' => 'SOR_120', 'uid' => null, 'isPilot' => false, 'controller' => $ctl];
SwuPgnTestEq(SwuPgnDiff(DiffSnap(['units' => [7 => DiffUnit('SOR_095', ['upgrades' => [$up(1)]])]]), DiffSnap(['units' => [7 => DiffUnit('SOR_095', ['upgrades' => [$up(2)]])]])), [
    ['k' => 'upgControl', 'seat' => 2, 'host' => 7, 'cardID' => 'SOR_120', 'from' => 1, 'to' => 2],
], 'an upgrade changing controller on its host');

SwuPgnTestFinish();
