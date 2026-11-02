<?php
// SWU-PGN/1.0 spec §10–§13: every fold rule, one hand-made case each.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_fold.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

// Folds $events (auto seq), optionally after an R1.start keyframe snap. Keyframes go through the
// parser so they look exactly like ones read from a file.
function F(array $events, ?array $kf = null, ?array &$warn = null): array
{
    if ($kf !== null) array_unshift($events, ['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf]);
    $doc = SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($events)));
    return SwuPgnFold($doc['events'], $warn);
}
function P(array $s, int $seat): array { return $s['players'][$seat]; }
function C(array $s, string $id): ?array
{
    foreach ([1, 2] as $seat) foreach ($s['players'][$seat]['cards'] as $c) if (($c['id'] ?? null) === $id) return $c + ['_seat' => $seat];
    return null;
}
function M(string $card, string $from, string $to, ?int $p = 1, array $x = []): array
{
    $e = ['t' => 'MOVE', 'card' => $card, 'from' => $from, 'to' => $to];
    if ($p !== null) $e['p'] = $p;
    return $e + $x;
}
$host = SwuPgnTestCard('SOR#095', 'ground');
$kfHost = SwuPgnTestKeyframe(['cards' => [$host], 'deckSize' => 5, 'hand' => ['SOR#200'], 'handSize' => 1]);

// ── §12.1 step 1/1a/1b: hand, discard, deck ─────────────────────────────────────────────────────
$s = F([M('SOR#001', 'deck', 'hand'), M('SOR#002', 'deck', 'hand')]);
SwuPgnTestEq([P($s, 1)['handSize'], P($s, 1)['hand']], [2, ['SOR#001', 'SOR#002']], '§12.1.1: deck→hand adds to handSize and to hand');
SwuPgnTestCheck(!array_key_exists('deckSize', P($s, 1)), '§12.1.1b: before a keyframe supplies deckSize it stays ABSENT (not 0, not negative)');
$s = F([M('SOR#001', 'deck', 'hand'), M('SOR#002', 'deck', 'hand'), M('SOR#003', 'hand', 'discard')], $kfHost);
SwuPgnTestEq(P($s, 1)['deckSize'], 3, '§12.1.1b: deckSize counts down once a keyframe supplied it');
SwuPgnTestEq([P($s, 1)['handSize'], P($s, 1)['discard']], [2, ['SOR#003']], '§12.1.1/1a: hand→discard: handSize−1, discard gains the card');
$s = F([M('SOR#200', 'hand', 'discard'), M('SOR#200', 'discard', 'hand'), M('SOR#300', 'hand', 'deck')], $kfHost);
SwuPgnTestEq([P($s, 1)['hand'], P($s, 1)['discard'], P($s, 1)['deckSize'], P($s, 1)['handSize']], [['SOR#200'], [], 6, 0], '§12.1.1a/1b: discard→hand removes from the pile; hand→deck adds to deckSize; handSize never below 0');
$s = F([M('SOR#001', 'deck', 'hand'), ['t' => 'DRAW', 'p' => 1, 'count' => 2, 'cards' => ['SOR#001', 'SOR#009']]]);
SwuPgnTestEq([P($s, 1)['handSize'], P($s, 1)['hand']], [1, ['SOR#001', 'SOR#009']], '§10.1 DRAW: adds ids once by id, never touches handSize');
$s = F([['t' => 'DISCARD', 'p' => 2, 'cards' => ['SOR#045', 'SOR#045']], ['t' => 'RESOURCE', 'p' => 2, 'card' => 'SOR#046']]);
SwuPgnTestEq([P($s, 2)['discard'], P($s, 2)['handSize'], P($s, 2)['resourcesReady']], [['SOR#045'], 0, 0], '§12.2 DISCARD adds once by id and nothing else; RESOURCE changes nothing');
$s = F([['t' => 'PLAY_EVENT', 'p' => 1, 'card' => 'SOR#150'], M('SOR#150', 'hand', 'discard')]);
SwuPgnTestEq(P($s, 1)['discard'], ['SOR#150'], '§12.2 PLAY_EVENT files the card once; its own MOVE is a no-op on the pile');

// ── MOVE rule violations: ignored, never a crash ────────────────────────────────────────────────
$base = F([M('SOR#001', 'deck', 'hand')]);
foreach ([
    'from equal to to' => M('SOR#001', 'hand', 'hand'),
    'empty from' => M('SOR#001', '', 'hand'),
    'zone outside §6.2' => M('SOR#001', 'hand', 'groundArena'),
    'missing card' => ['t' => 'MOVE', 'from' => 'hand', 'to' => 'discard', 'p' => 1],
    'non-string to' => ['t' => 'MOVE', 'card' => 'SOR#001', 'from' => 'hand', 'to' => 5, 'p' => 1],
] as $what => $bad) {
    $w = null;
    $s = F([M('SOR#001', 'deck', 'hand'), $bad], null, $w);
    SwuPgnTestCheck(SwuPgnStateToJson($s) === SwuPgnStateToJson($base) && count($w) === 1, "§10.1: a MOVE with $what is ignored (and warned about)");
}

// ── MOVE without p (§12.1) ──────────────────────────────────────────────────────────────────────
$s = F([M('SOR#095', 'ground', 'space', null), M('SOR#500', 'hand', 'ground', null), M('SOR#200', 'hand', 'discard', null)], $kfHost);
SwuPgnTestEq([C($s, 'SOR#095')['zone'] ?? null, C($s, 'SOR#500'), P($s, 1)['handSize'], P($s, 1)['discard']], ['space', null, 1, []], '§12.1: no p → only a tracked card\'s zone changes; nothing is created or counted');
$s = F([M('SOR#095', 'ground', 'space', 3)], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['zone'] ?? null, 'space', '§12.1: a p that is not seat 1/2 is treated as missing');

// ── §12.1 step 2: resource row ──────────────────────────────────────────────────────────────────
$s = F([M('SOR#010', 'hand', 'resource'), M('SOR#011', 'hand', 'resource')], SwuPgnTestKeyframe());
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 1)['resources'] ?? 'absent'], [2, ['SOR#010', 'SOR#011']], '§12.1.2: into resource: ready+1 and the card joins resources[] (created on first move)');
$s = F([], SwuPgnTestKeyframe());
SwuPgnTestCheck(!array_key_exists('resources', P($s, 1)), '§12.1.2: resources[] stays ABSENT until a move or keyframe supplies it');
$kfRes = SwuPgnTestKeyframe(['resourcesReady' => 2, 'resourcesExhausted' => 1, 'resources' => ['A#1', 'A#2', 'A#3']]);
$s = F([M('A#1', 'resource', 'hand', 1, ['exhausted' => true]), M('A#2', 'resource', 'hand')], $kfRes);
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 1)['resourcesExhausted'], P($s, 1)['resources']], [1, 0, ['A#3']], '§12.1.2: out of resource with exhausted:true takes from exhausted, otherwise from ready; leaves resources[]');
$s = F([M('A#1', 'resource', 'discard', 1, ['exhausted' => true]), M('A#2', 'resource', 'discard', 1, ['exhausted' => true])], $kfRes);
SwuPgnTestEq(P($s, 1)['resourcesExhausted'], 0, '§12.1.2: exhausted count never below 0');
$s = F([M('A#9', 'resource', 'hand')], SwuPgnTestKeyframe(['resourcesReady' => 1]));
SwuPgnTestCheck(P($s, 1)['resourcesReady'] === 0 && !array_key_exists('resources', P($s, 1)), '§12.1.2: a move out leaves an absent resources[] absent');
$s = F([['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 5]], $kfRes);
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 1)['resourcesExhausted']], [0, 3], '§10.1 EXHAUST_RESOURCES moves min(amount, ready)');
$s = F([['t' => 'READY_RESOURCES', 'p' => 1, 'amount' => 9]], $kfRes);
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 1)['resourcesExhausted']], [3, 0], '§10.1 READY_RESOURCES moves min(amount, exhausted)');
$s = F([['t' => 'READY_RESOURCES', 'p' => 1, 'amount' => -4], ['t' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 'x']], $kfRes);
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 1)['resourcesExhausted']], [2, 1], 'a negative or non-integer amount moves nothing');

// ── §12.1 step 2b: credits and the Force ────────────────────────────────────────────────────────
$s = F([M('TOKEN:credit#111', 'outsideTheGame', 'base'), M('TOKEN:credit#111:2', 'outsideTheGame', 'base'), M('TOKEN:credit', 'outsideTheGame', 'base', 2)]);
SwuPgnTestEq([P($s, 1)['credits'], P($s, 2)['credits']], [2, 1], '§12.1.2b: a TOKEN:credit moving into base is +1 credit (copies and the degraded form count)');
$s = F([M('TOKEN:credit#111', 'base', 'outsideTheGame'), M('TOKEN:the-force#222', 'outsideTheGame', 'base', 2), M('TOKEN:advantage#3', 'outsideTheGame', 'base')]);
SwuPgnTestEq([P($s, 1)['credits'], P($s, 2)['hasForce'], P($s, 1)['hasForce']], [0, true, false], '§12.1.2b: credits never below 0; the Force into base sets hasForce; other tokens in base do nothing');
$s = F([M('TOKEN:the-force#222', 'outsideTheGame', 'base'), M('TOKEN:the-force#222', 'base', 'outsideTheGame')]);
SwuPgnTestEq(P($s, 1)['hasForce'], false, '§12.1.2b: the Force out of base clears hasForce');

// ── §12.1 step 3 / §12.2: in-play list ──────────────────────────────────────────────────────────
$s = F([M('SOR#108', 'hand', 'ground'), ['t' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground']]);
SwuPgnTestEq(P($s, 1)['cards'], [['id' => 'SOR#108', 'zone' => 'ground', 'damage' => 0, 'exhausted' => false, 'upgrades' => [], 'shields' => 0, 'experience' => 0, 'statusTokens' => [], 'captured' => []]], '§12.2 newCard() shape; PLAY after its MOVE adds nothing (idempotent by id)');
SwuPgnTestCheck(strpos(SwuPgnStateToJson($s), '"statusTokens":{}') !== false, '§11: an empty statusTokens serialises as {} (an object), not []');
$odd = SwuPgnEmptyState();
$odd['players'] = [0 => ['seat' => 0], 1 => $odd['players'][1], 2 => $odd['players'][2]];
SwuPgnTestCheck(strpos(SwuPgnStateToJson($odd), '"players":{"0":') !== false, '§11: players always serialises as an object keyed by seat, even when its keys happen to form a list');
$s = F([['t' => 'PLAY', 'p' => 2, 'card' => 'SOR#045'], ['t' => 'PLAY_SMUGGLE', 'p' => 2, 'card' => 'SOR#046', 'zone' => 'space'], ['t' => 'PLAY', 'p' => 2, 'card' => 'SOR#047', 'zone' => 'discard']]);
SwuPgnTestEq([C($s, 'SOR#045')['zone'] ?? null, C($s, 'SOR#046')['zone'] ?? null, C($s, 'SOR#047')], ['ground', 'space', null], '§12.2 PLAY places in zone ?? "ground"; PLAY_SMUGGLE the same; a non-arena zone places nothing');
$s = F([M('SOR#095', 'ground', 'space')], $kfHost);
SwuPgnTestEq([count(P($s, 1)['cards']), C($s, 'SOR#095')['zone'] ?? null], [1, 'space'], '§12.1.3: arena→arena just sets the zone');
$s = F([M('SOR#095', 'ground', 'discard'), ['t' => 'DEFEAT', 'card' => 'SOR#095', 'reason' => 'attack']], $kfHost);
SwuPgnTestEq([P($s, 1)['cards'], P($s, 1)['discard']], [[], ['SOR#095']], '§12.1.1a: the MOVE files the defeated unit; the DEFEAT after it adds nothing');
$s = F([['t' => 'DEFEAT', 'card' => 'SOR#095', 'reason' => 'ability']], $kfHost);
SwuPgnTestEq([P($s, 1)['cards'], P($s, 1)['discard']], [[], ['SOR#095']], '§12.2 DEFEAT alone takes the card out of play and files it');
$s = F([['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:battle-droid#9', 'zone' => 'ground', 'kind' => 'unit'], M('TOKEN:battle-droid#9', 'outsideTheGame', 'ground', 1, ['kind' => 'unit'])]);
SwuPgnTestEq(count(P($s, 1)['cards']), 1, '§12.2 CREATE_TOKEN places the token; its MOVE does not duplicate it');
$s = F([['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:shield#8', 'zone' => 'ground', 'kind' => 'upgrade'], ['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:x-wing#7', 'zone' => 'outsideTheGame']]);
SwuPgnTestEq(P($s, 1)['cards'], [], '§12.2 CREATE_TOKEN: kind upgrade or a non-arena zone places nothing');
$s = F([['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:x-wing#7', 'zone' => 'space', 'power' => 2, 'hp' => 2]]);
SwuPgnTestCheck(!array_key_exists('power', C($s, 'TOKEN:x-wing#7') ?? []), '§10.1 CREATE_TOKEN power/hp are not folded — STATS states live values');
$s = F([M('SOR#500', 'hand', 'ground', 1, ['kind' => 'gadget'])]);
SwuPgnTestEq(C($s, 'SOR#500')['zone'] ?? null, 'ground', '§6.5: a kind other than "upgrade" (or absent) folds as a unit move');

// ── attachments (§10.1 binding rule, §12.1 step 0/3) ────────────────────────────────────────────
$s = F([M('LOF#215', 'hand', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095']),
    ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'zone' => 'ground', 'target' => 'SOR#095']], $kfHost);
SwuPgnTestEq([C($s, 'SOR#095')['upgrades'], C($s, 'LOF#215'), P($s, 1)['handSize']], [['LOF#215'], null, 0], '§12.1.3: an upgrade move attaches via attachedTo, never joins the arena, still leaves the hand; PLAY_UPGRADE is idempotent');
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'target' => 'SOR#095']], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['upgrades'], ['LOF#215'], '§12.2 PLAY_UPGRADE alone attaches to its target');
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'target' => 'SOR#999', 'zone' => 'ground'], ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#216', 'zone' => 'ground']], $kfHost);
SwuPgnTestEq([P($s, 1)['cards'][0]['upgrades'], count(P($s, 1)['cards'])], [[], 1], '§12.2 PLAY_UPGRADE: untracked target or no target → nothing (zone is not a fallback placement)');
$s = F([M('TOKEN:shield#5', 'outsideTheGame', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095']), ['t' => 'SHIELD_GAIN', 'card' => 'SOR#095']], $kfHost);
SwuPgnTestEq([C($s, 'SOR#095')['upgrades'], C($s, 'SOR#095')['shields'], count(P($s, 1)['cards'])], [[], 1, 1], '§12.2 attach(): a TOKEN upgrade never enters upgrades[]; its gain record counts it');
$s = F([M('LOF#215', 'hand', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095']), M('LOF#215', 'ground', 'discard', 1, ['kind' => 'upgrade'])], $kfHost);
SwuPgnTestEq([C($s, 'SOR#095')['upgrades'], P($s, 1)['discard']], [[], ['LOF#215']], '§12.1.0: an exit is host-less — leaving the arena detaches from every host');
$s = F([M('JTL#058', 'hand', 'space', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095']), M('JTL#058', 'space', 'discard', 1, ['kind' => 'unit'])], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['upgrades'], [], '§10.1: a pilot leaves as kind "unit" and is still detached on the zone transition');
SwuPgnTestCheck(C($s, 'JTL#058') === null, '§10.1: a pilot flown in as an upgrade never became an arena card');
$s = F([M('LOF#215', 'hand', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095']), ['t' => 'DEFEAT', 'card' => 'LOF#215', 'reason' => 'frameworkEffect']], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['upgrades'], [], '§12.2 DEFEAT detaches an upgrade');
// §13 (2026-10-28): a keyframe card MUST carry upgrades/statusTokens (it is damaged otherwise); only `captured` may be absent.
$kfOld = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['captured' => null])]], ['cards' => [SwuPgnTestCard('SOR#045')]]);
$s = F([M('SOR#045', 'ground', 'capture', 2), ['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'SOR#095']], $kfOld);
SwuPgnTestEq(C($s, 'SOR#095')['captured'] ?? null, ['SOR#045'], '§11: a keyframe card without captured (older file) still takes a captive');

// ── the token gain/removal contract (§10.1) ─────────────────────────────────────────────────────
$s = F([['t' => 'SHIELD_GAIN', 'card' => 'SOR#095'], ['t' => 'SHIELD_GAIN', 'card' => 'SOR#095', 'count' => 2], ['t' => 'SHIELD_USE', 'card' => 'SOR#095']], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['shields'], 2, '§12.2 SHIELD_GAIN count ?? 1; SHIELD_USE count ?? 1');
$s = F([['t' => 'SHIELD_USE', 'card' => 'SOR#095', 'count' => 3]], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['shields'], 0, '§12.2 SHIELD_USE never below 0');
$s = F([['t' => 'EXPERIENCE_GAIN', 'card' => 'SOR#095', 'count' => 2], ['t' => 'EXPERIENCE_GAIN', 'card' => 'SOR#095', 'count' => -1]], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['experience'], 1, '§12.2 EXPERIENCE_GAIN adds count; a negative count is the removal');
$s = F([['t' => 'EXPERIENCE_GAIN', 'card' => 'SOR#095', 'count' => -5]], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['experience'], 0, 'token rule 3: experience clamps at 0');
$s = F([['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => 1], ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'weakness', 'count' => 2],
    ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => -1], ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'weakness', 'count' => -1]], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['statusTokens'], ['weakness' => 1], 'token rule 4: a key that reaches zero is DELETED, others keep counting');
$s = F([['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => 1], ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => -3], ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'weakness', 'count' => -1]], $kfHost);
SwuPgnTestCheck(C($s, 'SOR#095')['statusTokens'] === [] && strpos(SwuPgnStateToJson($s), '"statusTokens":{}') !== false, 'token rules 3+4: an over-removal clamps and deletes; a removal with no gain never creates a 0 key ({} not {"advantage":0})');

// ── capture / rescue (§10.1) ────────────────────────────────────────────────────────────────────
$kfCap = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')]], ['cards' => [SwuPgnTestCard('SOR#045')]]);
$s = F([M('SOR#045', 'ground', 'capture', 2), ['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'SOR#095'], ['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'SOR#095']], $kfCap);
SwuPgnTestEq([C($s, 'SOR#095')['captured'], P($s, 2)['cards']], [['SOR#045'], []], '§12.2 CAPTURE files the card under its captor, idempotently; the MOVE took it off the board');
$s = F([['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'base@1']], $kfCap);
SwuPgnTestEq([P($s, 2)['cards'], C($s, 'SOR#095')['captured']], [[], []], '§12.2 CAPTURE removes the card from play even alone; a base captor holds it nowhere');
$s = F([M('SOR#045', 'ground', 'capture', 2), ['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'SOR#095'], ['t' => 'RESCUE', 'p' => 2, 'card' => 'SOR#045'], M('SOR#045', 'capture', 'ground', 2)], $kfCap);
SwuPgnTestEq([C($s, 'SOR#095')['captured'], C($s, 'SOR#045')['_seat'] ?? null], [[], 2], '§12.2 RESCUE detaches; the MOVE out of capture places the card again');
$s = F([M('SOR#045', 'ground', 'capture', 2), ['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'SOR#095'], M('SOR#045', 'capture', 'ground', 2), ['t' => 'RESCUE', 'p' => 2, 'card' => 'SOR#045']], $kfCap);
SwuPgnTestEq([C($s, 'SOR#095')['captured'], count(P($s, 2)['cards'])], [[], 1], '§10.1: rescue works whichever of MOVE / RESCUE comes first (MOVE from capture detaches)');

// ── TAKE_CONTROL (§10.1) ────────────────────────────────────────────────────────────────────────
$kfTc = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')], 'resourcesReady' => 0, 'resourcesExhausted' => 0, 'credits' => 0],
    ['cards' => [SwuPgnTestCard('SOR#045', 'space', ['damage' => 2, 'upgrades' => ['LOF#215'], 'experience' => 1])], 'resourcesReady' => 2, 'resourcesExhausted' => 1, 'credits' => 1, 'hasForce' => true]);
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#045', 'zone' => 'space']], $kfTc);
SwuPgnTestEq([count(P($s, 2)['cards']), C($s, 'SOR#045')['_seat'] ?? null, C($s, 'SOR#045')['damage'] ?? null, C($s, 'SOR#045')['upgrades'] ?? null], [0, 1, 2, ['LOF#215']], '§12.2 TAKE_CONTROL (arena) re-seats the whole card entry');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#095', 'zone' => 'ground'], ['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#777', 'zone' => 'ground']], $kfTc);
SwuPgnTestEq([count(P($s, 1)['cards']), P($s, 1)['cards'][0]['id']], [1, 'SOR#095'], '§12.2 TAKE_CONTROL of a card the seat already holds, or an untracked card, changes nothing');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'R#1', 'zone' => 'resource', 'from' => 2], ['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'R#2', 'zone' => 'resource', 'from' => 2, 'exhausted' => true]], $kfTc);
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 1)['resourcesExhausted'], P($s, 2)['resourcesReady'], P($s, 2)['resourcesExhausted']], [1, 1, 1, 0], '§12.2 TAKE_CONTROL resource+from shifts one resource, in the bucket `exhausted` names');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'R#3', 'zone' => 'resource', 'from' => 1, 'exhausted' => true]], $kfTc);
SwuPgnTestEq([P($s, 1)['resourcesExhausted'], P($s, 2)['resourcesExhausted']], [0, 2], '§10.1: never below 0 on the losing side');
// §10.1 "This record therefore re-seats the card itself" + §11/§14 resources[] is membership and is gated:
// a stolen resource changes WHICH row it is in, not just the counts.
$kfRow = SwuPgnTestKeyframe(['resources' => ['A#1'], 'resourcesReady' => 1], ['resources' => ['R#1', 'R#2'], 'resourcesReady' => 2]);
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'R#1', 'zone' => 'resource', 'from' => 2]], $kfRow);
SwuPgnTestEq([P($s, 1)['resources'] ?? null, P($s, 2)['resources'] ?? null], [['A#1', 'R#1'], ['R#2']], '§10.1 TAKE_CONTROL resource+from re-seats the card in resources[] as well as the counts');
$integrity = SwuPgnCheckKeyframes([
    ['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => $kfRow],
    ['seq' => 'R1.A.1', 't' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'R#1', 'zone' => 'resource', 'from' => 2],
    ['seq' => 'R1.end', 't' => 'ROUND_END', 'round' => 1, 'keyframe' => SwuPgnTestKeyframe(['resources' => ['A#1', 'R#1'], 'resourcesReady' => 2], ['resources' => ['R#2'], 'resourcesReady' => 1])],
]);
SwuPgnTestEq(array_values(array_filter($integrity['mismatches'], fn($m) => $m['seq'] === 'R1.end')), [],
    '§14 the keyframe after a resource steal reconciles (no false resources mismatch)');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'R#1', 'zone' => 'resource']], $kfTc);
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 2)['resourcesReady']], [0, 2], '§10.1: TAKE_CONTROL resource WITHOUT from moves nothing (a MOVE carried the counts)');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'TOKEN:credit#1', 'zone' => 'base', 'from' => 2], ['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'TOKEN:the-force#2', 'zone' => 'base', 'from' => 2]], $kfTc);
SwuPgnTestEq([P($s, 1)['credits'], P($s, 2)['credits'], P($s, 1)['hasForce'], P($s, 2)['hasForce']], [1, 0, true, false], '§12.2 TAKE_CONTROL base+from shifts a credit / the Force');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#045', 'zone' => 'hand', 'from' => 2], ['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#045']], $kfTc);
SwuPgnTestEq(C($s, 'SOR#045')['_seat'] ?? null, 2, '§12.2 TAKE_CONTROL with any other zone, or none, does nothing');

// ── leaders (§10.1 DEPLOY_LEADER, LEADER_FLIP, ABILITY_ACTIVATE, §12.1 step 1c) ─────────────────
$kfL = SwuPgnTestKeyframe(['leader' => ['id' => 'SOR#010', 'deployed' => false, 'exhausted' => true, 'epicActionUsed' => false]], ['cards' => [SwuPgnTestCard('JTL#100', 'space')], 'leader' => ['id' => 'TWI#017', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false, 'onStartingSide' => true]]);
$s = F([['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'epic' => true]], $kfL);
SwuPgnTestEq([P($s, 1)['leader'], C($s, 'SOR#010')['zone'] ?? null], [['id' => 'SOR#010', 'deployed' => true, 'exhausted' => false, 'epicActionUsed' => true], 'ground'], '§12.2 DEPLOY_LEADER: deployed, READY whatever it was, epic → epicActionUsed; placed in zone ?? ground');
$s = F([['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'epic' => true], M('SOR#010', 'ground', 'base'), ['t' => 'EXHAUST', 'card' => 'SOR#010'], ['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'zone' => 'space']], $kfL);
SwuPgnTestEq([P($s, 1)['leader']['epicActionUsed'], P($s, 1)['leader']['deployed'], C($s, 'SOR#010')['zone'] ?? null], [true, true, 'space'], '§12.2 DEPLOY_LEADER: an Epic Action already used stays used');
$s = F([['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010'], M('SOR#010', 'ground', 'base'), ['t' => 'EXHAUST', 'card' => 'SOR#010']], $kfL);
SwuPgnTestEq([P($s, 1)['leader'], C($s, 'SOR#010')], [['id' => 'SOR#010', 'deployed' => false, 'exhausted' => true, 'epicActionUsed' => false], null], '§12.1.1c: the leader\'s move from an arena to base undeploys it; the EXHAUST beside it sets its flag');
$s = F([['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'upgrade', 'target' => 'JTL#100', 'zone' => 'space', 'epic' => true]], $kfL);
SwuPgnTestEq([C($s, 'JTL#100')['upgrades'], C($s, 'SOR#010'), P($s, 1)['leader']['deployed']], [['SOR#010'], null, true], '§12.2 DEPLOY_LEADER kind:"upgrade" attaches to target and is never an arena card');
$s = F([['t' => 'READY', 'card' => 'SOR#010']], $kfL);
SwuPgnTestEq(P($s, 1)['leader']['exhausted'], false, '§12.2 READY on the leader\'s id readies the leader in the base zone');
$s = F([['t' => 'LEADER_FLIP', 'p' => 2, 'card' => 'TWI#017', 'onStartingSide' => false], ['t' => 'LEADER_FLIP', 'p' => 2, 'card' => 'TWI#017', 'onStartingSide' => false]], $kfL);
SwuPgnTestEq(P($s, 2)['leader']['onStartingSide'], false, '§12.2 LEADER_FLIP states the face; applying it twice is the same as once');
$s = F([['t' => 'LEADER_FLIP', 'p' => 2, 'card' => 'TWI#017-alt', 'onStartingSide' => false]], $kfL);
SwuPgnTestEq([P($s, 2)['leader']['onStartingSide'], P($s, 2)['leader']['id']], [false, 'TWI#017'], '§12.2 LEADER_FLIP falls back to the record\'s p');
$s = F([['t' => 'LEADER_FLIP', 'p' => 1, 'card' => 'TWI#017', 'onStartingSide' => false]]);
SwuPgnTestEq(P($s, 1)['leader'] ?? null, ['id' => 'TWI#017', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false, 'onStartingSide' => false], 'LEADER_FLIP before any keyframe creates the seat\'s leader (in the base zone)');
SwuPgnTestCheck(!array_key_exists('onStartingSide', P(F([], $kfL), 1)['leader']), '§10.1: onStartingSide stays ABSENT for a single-sided leader');
$s = F([['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'epic', 'epic' => true]], $kfL);
SwuPgnTestEq([P($s, 1)['leader']['epicActionUsed'], array_key_exists('baseEpicActionUsed', P($s, 1))], [true, false], '§12.2 ABILITY_ACTIVATE epic on the leader\'s id marks the leader\'s Epic Action only');
$s = F([['t' => 'ABILITY_ACTIVATE', 'p' => 2, 'card' => 'base@2', 'kind' => 'epic', 'epic' => true]], $kfL);
SwuPgnTestEq([P($s, 2)['baseEpicActionUsed'] ?? null, P($s, 2)['leader']['epicActionUsed']], [true, false], '§6.3/§12.2 ABILITY_ACTIVATE epic on base@N marks that seat\'s baseEpicActionUsed, not the leader');
$s = F([['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'action']], $kfL);
SwuPgnTestEq(P($s, 1)['leader']['epicActionUsed'], false, '§12.2 a non-epic ABILITY_ACTIVATE changes nothing');
$s = F([['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'epic']], $kfL);
SwuPgnTestEq(P($s, 1)['leader']['epicActionUsed'], true, '§10.1: `epic` is "equivalent to kind: epic" — kind alone also marks it');

// ── STATS, DAMAGE, HEAL, OVERWHELM, EXHAUST ─────────────────────────────────────────────────────
$s = F([['t' => 'STATS', 'card' => 'SOR#095', 'power' => 3, 'hp' => 4, 'keywords' => ['sentinel', 'raid 2', 'overwhelm']], ['t' => 'STATS', 'card' => 'SOR#095', 'power' => 5, 'hp' => 4], ['t' => 'STATS', 'card' => 'SOR#999', 'power' => 1, 'hp' => 1]], $kfHost);
SwuPgnTestEq([C($s, 'SOR#095')['power'], C($s, 'SOR#095')['hp'], C($s, 'SOR#095')['keywords'], C($s, 'SOR#999')], [5, 4, ['overwhelm', 'raid 2', 'sentinel'], null], '§12.2 STATS sets power/hp, keywords sorted when given; an untracked card is ignored');
$s = F([['t' => 'DAMAGE', 'src' => 'X', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'combat', 'hp' => 21], ['t' => 'DAMAGE', 'src' => 'X', 'tgt' => 'SOR#095', 'amt' => 3, 'damageType' => 'combat', 'hp' => 2]], $kfHost);
SwuPgnTestEq([P($s, 2)['baseHp'], C($s, 'SOR#095')['damage']], [21, 3], '§12.2 DAMAGE: base takes the file\'s hp (not 30−4); a card adds amt');
$s = F([['t' => 'DAMAGE', 'src' => 'X', 'tgt' => 'SOR#095', 'amt' => 3, 'damageType' => 'combat', 'hp' => 2], ['t' => 'HEAL', 'tgt' => 'SOR#095', 'amt' => 5, 'hp' => 5], ['t' => 'HEAL', 'src' => 'Y', 'tgt' => 'base@1', 'amt' => 2, 'hp' => 29]], $kfHost);
SwuPgnTestEq([C($s, 'SOR#095')['damage'], P($s, 1)['baseHp']], [0, 29], '§12.2 HEAL: card damage never below 0; base takes hp');
$s = F([['t' => 'DAMAGE', 'src' => 'X', 'tgt' => 'SOR#095', 'amt' => 1, 'damageType' => 'combat', 'hp' => 2], ['t' => 'OVERWHELM', 'p' => 1, 'tgt' => 'base@2', 'amt' => 2, 'hp' => 28], ['t' => 'OVERWHELM', 'p' => 1, 'tgt' => 'SOR#095', 'amt' => 2, 'hp' => 1]], $kfHost);
SwuPgnTestEq([P($s, 2)['baseHp'], C($s, 'SOR#095')['damage']], [28, 1], '§12.2 OVERWHELM: base@N sets baseHp; anything else does nothing');
$s = F([['t' => 'EXHAUST', 'card' => 'SOR#095'], ['t' => 'EXHAUST', 'card' => 'SOR#999']], $kfHost);
SwuPgnTestEq(C($s, 'SOR#095')['exhausted'], true, '§12.2 EXHAUST on an arena card; an unknown id is silently ignored');

// ── round, phase, initiative ────────────────────────────────────────────────────────────────────
$s = F([['t' => 'CLAIM_INITIATIVE', 'p' => 2]]);
SwuPgnTestEq([$s['initiative'], $s['initiativeTaken']], [2, true], '§12.2 CLAIM_INITIATIVE');
$s = F([['t' => 'CLAIM_INITIATIVE', 'p' => 2], ['t' => 'ROUND_START', 'round' => 3], ['t' => 'PHASE_START', 'phase' => 'regroup']]);
SwuPgnTestEq([$s['round'], $s['initiativeTaken'], $s['initiative'], $s['phase']], [3, false, 2, 'regroup'], '§12.2 ROUND_START sets round and resets initiativeTaken; PHASE_START sets phase');
$e = SwuPgnEmptyState();
SwuPgnTestEq([$e['round'], $e['phase'], $e['initiative'], array_keys($e['players']), $e['players'][1]['baseHp'], $e['initiativeTaken'] ?? 'absent'], [0, 'setup', null, [1, 2], 30, false], '§11 starting board: round 0, setup, no initiative, initiativeTaken false, both seats at the 30 placeholder');
SwuPgnTestEq(array_keys($e), ['round', 'phase', 'initiative', 'initiativeTaken', 'players'], '§11 starting board: no active, no effects (absent until stated)');

// ── notes and unknown types ─────────────────────────────────────────────────────────────────────
$w = null;
$notes = [];
foreach (['ATTACK', 'PASS', 'CHOICE', 'MODAL_CHOICE', 'MULLIGAN', 'KEEP_HAND', 'SHUFFLE', 'SEARCH', 'REVEAL', 'TRIGGER', 'PHASE_END', 'ROUND_END', 'GAME_END', 'UNDO'] as $t) $notes[] = ['t' => $t, 'p' => 1, 'card' => 'SOR#095', 'cards' => ['SOR#095']];
$s = F($notes, $kfHost, $w);
SwuPgnTestCheck(SwuPgnStateToJson($s) === SwuPgnStateToJson(F([], $kfHost)) && $w === [], '§10.2: every note type folds to nothing, silently');
$s = F([['t' => 'FUTURE_THING', 'p' => 1], ['t' => 'FUTURE_THING'], ['t' => 'OTHER_NEW']], $kfHost, $w);
SwuPgnTestCheck(SwuPgnStateToJson($s) === SwuPgnStateToJson(F([], $kfHost)) && count($w) === 2, '§18: unknown types do nothing and warn (once per type)');

// ── keyframes (§12, §13) ────────────────────────────────────────────────────────────────────────
$kf = SwuPgnTestKeyframe(['handSize' => 4, 'deckSize' => 20, 'leader' => ['id' => 'SOR#010', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false]], [], ['round' => 2, 'active' => 2]);
$s = F([M('SOR#001', 'hand', 'resource'), ['t' => 'ROUND_END', 'round' => 1, 'keyframe' => $kf]]);
SwuPgnTestEq($s, SwuPgnTestViaJson($kf), '§13: a complete ROUND_END keyframe REPLACES the state (resources[] the fold built is gone; active kept)');
$s = F([['t' => 'ROUND_START', 'round' => 9, 'keyframe' => $kf]]);
SwuPgnTestEq($s['round'], 2, '§12: a snapped keyframe skips the event\'s own rule (round comes from the keyframe)');
foreach ([
    'missing seat 2' => ['round' => 2, 'players' => ['1' => SwuPgnTestSeatKeyframe(1)]],
    'players {}' => ['round' => 2, 'players' => new stdClass()],
    'hand not an array' => SwuPgnTestKeyframe(['hand' => 'SOR#001']),
    'discard an object' => SwuPgnTestKeyframe([], ['discard' => ['a' => 'SOR#001']]),
    'cards missing' => SwuPgnTestKeyframe(['cards' => null]),
    'a cards entry not an object' => SwuPgnTestKeyframe(['cards' => ['SOR#095']]),
    'keyframe null' => null,
    'keyframe a list' => [1, 2],
    'seat not an object' => ['players' => ['1' => SwuPgnTestSeatKeyframe(1), '2' => 7]],
] as $what => $bad) {
    $w = null;
    $s = F([M('SOR#001', 'deck', 'hand'), ['t' => 'ROUND_START', 'round' => 4, 'keyframe' => $bad]], null, $w);
    SwuPgnTestCheck($s['round'] === 4 && P($s, 1)['hand'] === ['SOR#001'] && count($w) === 1, "§13: a damaged keyframe ($what) is ignored and warned; folding carries on with the event's own rule");
    SwuPgnTestCheck(SwuPgnKeyframeProblem($bad) !== null, "SwuPgnKeyframeProblem names the damage ($what)");
}
SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson(SwuPgnTestKeyframe(['cards' => [new stdClass()]]))) !== null, '§13 (10-28): an empty-object card entry lacks upgrades/statusTokens/damage/shields/experience — damaged');
SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson($kf)) === null, 'SwuPgnKeyframeProblem: a complete keyframe has no problem');
$s = F([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => SwuPgnTestKeyframe([], [], ['round' => 1])], ['t' => 'ROUND_START', 'round' => 2]]);
SwuPgnTestEq([$s['round'], $s['initiativeTaken']], [2, false], 'a ROUND_START without a keyframe just keeps folding (valid and harmless)');

// ═══ 2026-10-28 text ════════════════════════════════════════════════════════════════════════════
// §13 damaged-keyframe list, one case per condition.
$card = SwuPgnTestCard('SOR#095');
$big = array_map(fn($i) => "X#$i", range(1, 1001));
foreach ([
    'players a list' => ['round' => 1, 'players' => [SwuPgnTestSeatKeyframe(1), SwuPgnTestSeatKeyframe(1), SwuPgnTestSeatKeyframe(2)]],
    'resources not an array' => SwuPgnTestKeyframe(['resources' => 'A#1']),
    'foreignResources not an array' => SwuPgnTestKeyframe([], ['foreignResources' => 5]),
    'baseCaptured an object' => SwuPgnTestKeyframe(['baseCaptured' => ['x' => 'A#1']]),
    'hand of 1001' => SwuPgnTestKeyframe(['hand' => $big]),
    'foreignResources of 1001' => SwuPgnTestKeyframe(['foreignResources' => $big]),
    'card without upgrades' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgrades' => null])]]),
    'card with 1001 upgrades' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgrades' => $big])]]),
    'card without statusTokens' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['statusTokens' => null])]]),
    'statusTokens a list' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['statusTokens' => [1, 2]])]]),
    'damage not a number' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['damage' => '2'])]]),
    'shields missing' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['shields' => null])]]),
    'experience missing' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['experience' => null])]]),
    'captured not an array' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['captured' => 'A#1'])]]),
    'captured of 1001' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['captured' => $big])]]),
    'seat over 1000 upgrades+captured' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('H#1', 'ground', ['upgrades' => array_slice($big, 0, 600)]), SwuPgnTestCard('H#2', 'ground', ['captured' => array_slice($big, 600, 401)])]]),
    'upgradeControllers a list' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgradeControllers' => [2]])]]),
    'upgradeControllers a number' => SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgradeControllers' => 2])]]),
    'effects not an array' => SwuPgnTestKeyframe([], [], ['effects' => 'fx@1']),
    'effects of 1001' => SwuPgnTestKeyframe([], [], ['effects' => array_map(fn($i) => ['id' => "fx@$i", 'src' => 'A#1', 'p' => 1, 'duration' => 'phase'], range(1, 1001))]),
    'an effect without a string id' => SwuPgnTestKeyframe([], [], ['effects' => [['id' => 3, 'src' => 'A#1', 'p' => 1, 'duration' => 'phase']]]),
    'an effect that is not an object' => SwuPgnTestKeyframe([], [], ['effects' => ['fx@1']]),
    'effect targets not an array' => SwuPgnTestKeyframe([], [], ['effects' => [['id' => 'fx@1', 'src' => 'A#1', 'p' => 1, 'targets' => 'SOR#095', 'duration' => 'phase']]]),
    'effect targets of 1001' => SwuPgnTestKeyframe([], [], ['effects' => [['id' => 'fx@1', 'src' => 'A#1', 'p' => 1, 'targets' => $big, 'duration' => 'phase']]]),
    'abilityUses not an array' => SwuPgnTestKeyframe(['abilityUses' => 'x']),
    'abilityUses of 1001' => SwuPgnTestKeyframe(['abilityUses' => array_map(fn($i) => ['card' => 'A#1', 'ability' => "a$i", 'count' => 1, 'per' => 'game', 'max' => 1], range(1, 1001))]),
    'an abilityUses entry without a number count' => SwuPgnTestKeyframe(['abilityUses' => [['card' => 'A#1', 'ability' => 'a', 'count' => '1', 'per' => 'game', 'max' => 1]]]),
    'an abilityUses entry without a string ability' => SwuPgnTestKeyframe([], ['abilityUses' => [['card' => 'A#1', 'count' => 1, 'per' => 'game', 'max' => 1]]]),
] as $what => $bad) {
    SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson($bad)) !== null, "§13 damaged: $what");
}
$okKf = SwuPgnTestKeyframe(['foreignResources' => [], 'abilityUses' => [['card' => 'A#1', 'ability' => 'a', 'count' => 1, 'per' => 'mystery', 'max' => 1]],
    'cards' => [SwuPgnTestCard('SOR#095', 'ground', ['captured' => null, 'upgradeControllers' => ['A#2' => 2], 'keywords' => array_map(fn($i) => "k$i", range(1, 1001))])]],
    [], ['effects' => [['id' => 'fx@1', 'src' => 'A#1', 'p' => 1, 'seat' => 'both', 'duration' => 'phase']]]);
SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson($okKf)) === null, '§13: a current keyframe (effects, abilityUses, foreignResources, upgradeControllers, absent captured, long keywords) is not damaged', SwuPgnKeyframeProblem(SwuPgnTestViaJson($okKf)));
SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson(SwuPgnTestKeyframe(['hand' => array_slice($big, 0, 1000), 'cards' => [SwuPgnTestCard('H#1', 'ground', ['upgrades' => array_slice($big, 0, 500), 'captured' => array_slice($big, 500, 500)])]]))) === null, '§13: exactly 1000 (hand; seat upgrades+captured) is not over the cap');

// §12 caps: every list the fold grows ≤ 1000 (our old 500/200/32 dropped what a conformant file holds).
SwuPgnTestEq([SWUPGN_MAX_LIST, SWUPGN_MAX_CARDS_PER_SEAT, SWUPGN_MAX_ATTACHED, SWUPGN_MAX_TOKEN_KINDS, SWUPGN_MAX_SEAT_ATTACHED, SWUPGN_MAX_EFFECTS, SWUPGN_MAX_ABILITY_USES], [1000, 1000, 1000, 1000, 1000, 1000, 1000], '§12: the fold caps are the spec\'s 1000');
$ev = [];
for ($i = 1; $i <= 1001; $i++) $ev[] = ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => "U#$i", 'target' => 'SOR#095'];
$s = F($ev, $kfHost);
SwuPgnTestEq([count(C($s, 'SOR#095')['upgrades']), C($s, 'SOR#095')['upgrades'][999]], [1000, 'U#1000'], '§12: a card\'s upgrades hold 1000; entry 1001 is dropped');
// §12 / [F-05] a seat's cards hold at most 1000 upgrades and captives between them.
$full = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('H#1', 'ground', ['upgrades' => array_slice($big, 0, 999), 'captured' => ['C#0']]), SwuPgnTestCard('H#2')]],
    ['cards' => [SwuPgnTestCard('V#1'), SwuPgnTestCard('V#2', 'ground', ['upgrades' => ['W#1']])]]);
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'U#1', 'target' => 'H#2'], M('U#2', 'hand', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'H#2']),
    M('V#1', 'ground', 'capture', 2), ['t' => 'CAPTURE', 'p' => 1, 'card' => 'V#1', 'by' => 'H#2']], $full);
SwuPgnTestEq([C($s, 'H#2')['upgrades'], C($s, 'H#2')['captured'], C($s, 'V#1')], [[], [], null], '§12: no upgrade or captive is added to a card when its seat\'s cards already hold 1000');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'V#2', 'zone' => 'ground', 'from' => 2]], $full);
SwuPgnTestEq(C($s, 'V#2')['_seat'] ?? null, 2, '§12: a TAKE_CONTROL does not move a unit whose upgrades would take its new seat past 1000');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'V#1', 'zone' => 'ground', 'from' => 2]], $full);
SwuPgnTestEq(C($s, 'V#1')['_seat'] ?? null, 1, '§12: … a unit with nothing attached still moves into a full seat');

// §10.1 / §12.2 ROUND_START: active, initiative = active, uses reset.
$uses = [['card' => 'A#1', 'ability' => 'p', 'count' => 1, 'per' => 'phase', 'max' => 1], ['card' => 'A#1', 'ability' => 'r', 'count' => 1, 'per' => 'round', 'max' => 1],
    ['card' => 'A#1', 'ability' => 'g', 'count' => 1, 'per' => 'game', 'max' => 1]];
$kfU = SwuPgnTestKeyframe(['abilityUses' => $uses], ['abilityUses' => [$uses[0]]]);
$s = F([['t' => 'CLAIM_INITIATIVE', 'p' => 1], ['t' => 'ROUND_START', 'round' => 2, 'active' => 2]], $kfU);
SwuPgnTestEq([$s['round'], $s['active'] ?? null, $s['initiative'], $s['initiativeTaken']], [2, 2, 2, false], '§12.2 ROUND_START: active = event.active, then initiative = active; initiativeTaken false');
SwuPgnTestEq([array_column(P($s, 1)['abilityUses'], 'ability'), P($s, 2)['abilityUses']], [['g'], []], '§12.2 ROUND_START: removes every seat\'s phase and round uses; game uses stay; an emptied list stays (empty)');
SwuPgnTestEq(array_slice(array_keys($s), 0, 6), ['round', 'phase', 'initiative', 'initiativeTaken', 'active', 'players'], '§11: active sits in the board\'s key order');
$s = F([['t' => 'CLAIM_INITIATIVE', 'p' => 1], ['t' => 'ROUND_START', 'round' => 2, 'active' => 3], ['t' => 'ROUND_START', 'round' => 3]], $kfU);
SwuPgnTestEq([$s['initiative'], array_key_exists('active', $s)], [1, false], '§12.2 ROUND_START: an active that is not 1|2 (or none) leaves active and initiative alone');
$s = F([['t' => 'PHASE_START', 'phase' => 'action', 'active' => 1]], $kfU);
SwuPgnTestEq([$s['phase'], $s['active'] ?? null, array_column(P($s, 1)['abilityUses'], 'ability'), P($s, 2)['abilityUses'], $s['initiative']], ['action', 1, ['r', 'g'], [], 1], '§12.2 PHASE_START: phase, active when 1|2; removes phase uses only; initiative untouched');
$s = F([['t' => 'PHASE_START', 'phase' => 'regroup', 'active' => 'both']], $kfU);
SwuPgnTestCheck(!array_key_exists('active', $s), '§12.2 PHASE_START: an active that is not 1|2 is not taken');
$s = F([['t' => 'ROUND_START', 'round' => 2, 'active' => 1]]);
SwuPgnTestCheck(!array_key_exists('abilityUses', P($s, 1)), '§11: abilityUses stays absent until the seat\'s first limited use');

// §10.1 / §12.2 ABILITY_ACTIVATE.limit
$AA = function (int $p, string $card, $ability, $limit, array $x = []) { return $x + ['t' => 'ABILITY_ACTIVATE', 'p' => $p, 'card' => $card, 'ability' => $ability, 'kind' => 'triggered', 'limit' => $limit]; };
$s = F([$AA(1, 'A#1', 'x', ['max' => 1, 'per' => 'round']), $AA(1, 'A#1', 'x', ['max' => 2, 'per' => 'round']), $AA(1, 'A#1', 'y', ['max' => 1, 'per' => 'phase']), $AA(1, 'A#2', 'x', ['max' => 1, 'per' => 'game'])]);
SwuPgnTestEq(P($s, 1)['abilityUses'] ?? null, [['card' => 'A#1', 'ability' => 'x', 'count' => 2, 'per' => 'round', 'max' => 2], ['card' => 'A#1', 'ability' => 'y', 'count' => 1, 'per' => 'phase', 'max' => 1], ['card' => 'A#2', 'ability' => 'x', 'count' => 1, 'per' => 'game', 'max' => 1]],
    '§12.2 ABILITY_ACTIVATE limit: created at the first use, keyed card+ability, count+1, latest max kept, first-use order');
$s = F([$AA(1, 'A#1', 'x', ['max' => 'two', 'per' => 'game']), $AA(2, 'A#1', 'x', ['per' => 'phase'])]);
SwuPgnTestEq([P($s, 1)['abilityUses'][0]['max'] ?? null, P($s, 2)['abilityUses'][0]['max'] ?? null], [0, 0], '§12.2: max 0 when limit.max is not a number');
$s = F([$AA(1, 'A#1', 'x', ['max' => 1, 'per' => 'turn']), $AA(1, 'A#1', null, ['max' => 1, 'per' => 'game']), ['t' => 'ABILITY_ACTIVATE', 'p' => 3, 'card' => 'A#1', 'ability' => 'x', 'limit' => ['max' => 1, 'per' => 'game']],
    $AA(1, 'A#1', 'x', 'once'), ['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'A#1', 'ability' => 'x']]);
SwuPgnTestCheck(!array_key_exists('abilityUses', P($s, 1)) && !array_key_exists('abilityUses', P($s, 2)), '§12.2: no use counted for an unknown per, a missing ability, a seat that is not 1|2, a limit that is not an object, or no limit');
$s = F([$AA(1, 'SOR#010', 'epic-x', ['max' => 1, 'per' => 'game'], ['kind' => 'epic', 'epic' => true])], $kfL);
SwuPgnTestEq([count(P($s, 1)['abilityUses'] ?? []), P($s, 1)['leader']['epicActionUsed']], [1, true], '§12.2: a limited use is counted, then epic still marks the Epic Action');
$ev = [];
for ($i = 1; $i <= 1001; $i++) $ev[] = $AA(1, 'A#1', "a$i", ['max' => 1, 'per' => 'game']);
$s = F($ev);
SwuPgnTestEq(count(P($s, 1)['abilityUses']), 1000, '§12.2: abilityUses ≤ 1000 entries');

// §10.1 / §12.2 ABILITY_RESET
$kfR = SwuPgnTestKeyframe(['abilityUses' => [['card' => 'A#1', 'ability' => 'x', 'count' => 1, 'per' => 'game', 'max' => 1], ['card' => 'A#1', 'ability' => 'y', 'count' => 1, 'per' => 'game', 'max' => 1], ['card' => 'A#2', 'ability' => 'x', 'count' => 1, 'per' => 'game', 'max' => 1]]],
    ['abilityUses' => [['card' => 'A#1', 'ability' => 'z', 'count' => 1, 'per' => 'round', 'max' => 1]]]);
$s = F([['t' => 'ABILITY_RESET', 'card' => 'A#1', 'ability' => 'x']], $kfR);
SwuPgnTestEq([array_map(fn($u) => $u['card'] . '/' . $u['ability'], P($s, 1)['abilityUses']), count(P($s, 2)['abilityUses'])], [['A#1/y', 'A#2/x'], 1], '§12.2 ABILITY_RESET with ability: only that ability\'s entries for the card');
$s = F([['t' => 'ABILITY_RESET', 'card' => 'A#1'], ['t' => 'ABILITY_RESET', 'card' => 7]], $kfR);
SwuPgnTestEq([array_map(fn($u) => $u['card'] . '/' . $u['ability'], P($s, 1)['abilityUses']), P($s, 2)['abilityUses']], [['A#2/x'], []], '§12.2 ABILITY_RESET: every seat\'s entries for the card; a non-string card is ignored');
$s = F([['t' => 'ABILITY_RESET', 'card' => 'A#1']]);
SwuPgnTestCheck(!array_key_exists('abilityUses', P($s, 1)), '§12.2 ABILITY_RESET: a seat without abilityUses is left without');

// §10.1 / §12.2 EFFECT_START / EFFECT_END
// Overrides win; a null override drops the field.
$FX = function (string $id, array $x = []) { return array_filter(array_merge(['t' => 'EFFECT_START', 'id' => $id, 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#095', 'SOR#096'], 'duration' => 'phase', 'ability' => 'rc', 'text' => 'Raid 2', 'untilText' => 'x'], $x), fn($v) => $v !== null); };
$s = F([$FX('fx@1'), $FX('fx@2', ['targets' => null, 'seat' => 2, 'p' => 2, 'duration' => 'until'])]);
SwuPgnTestEq($s['effects'] ?? null, [['id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#095', 'SOR#096'], 'duration' => 'phase'], ['id' => 'fx@2', 'src' => 'SOR#154', 'p' => 2, 'seat' => 2, 'duration' => 'until']],
    '§12.2 EFFECT_START: effects created, holds id/src/p/targets?/seat?/duration — never untilText/ability/text — in start order');
SwuPgnTestEq(array_keys($s), ['round', 'phase', 'initiative', 'initiativeTaken', 'players', 'effects'], '§11: effects is the board\'s last key');
$s = F([$FX('fx@1'), $FX('fx@2'), $FX('fx@1', ['targets' => ['SOR#097'], 'duration' => 'round'])]);
SwuPgnTestEq([array_column($s['effects'], 'id'), $s['effects'][0]['targets'], $s['effects'][0]['duration']], [['fx@1', 'fx@2'], ['SOR#097'], 'round'], '§12.2 EFFECT_START: the same id is replaced in place');
$s = F([$FX('fx@1', ['seat' => 'both', 'targets' => null]), $FX('fx@2', ['seat' => 3, 'targets' => null])]);
SwuPgnTestEq([array_key_exists('seat', $s['effects'][0]), array_key_exists('seat', $s['effects'][1]), array_key_exists('targets', $s['effects'][0])], [false, false, false], '§12.2 EFFECT_START: seat only when 1 or 2 (the table\'s guard; "both" is not kept), targets only when given');
$s = F([$FX('fx@1', ['p' => 3]), $FX('fx@2', ['src' => null]), $FX('fx@3', ['duration' => 5]), $FX('fx@4', ['id' => null])]);
SwuPgnTestCheck(!array_key_exists('effects', $s), '§12.2 EFFECT_START: ignored unless id, src, duration strings and p 1|2 (and effects stays absent)');
$s = F([$FX('fx@1', ['targets' => ['SOR#095', 7, 'SOR#096']])]);
SwuPgnTestEq($s['effects'][0]['targets'], ['SOR#095', 'SOR#096'], '§12.2 EFFECT_START: targets keeps the strings');
$ev = [];
for ($i = 1; $i <= 1001; $i++) $ev[] = $FX("fx@$i");
$s = F($ev);
SwuPgnTestEq(count($s['effects']), 1000, '§12.2 EFFECT_START: ≤ 1000 effects');
$s = F([$FX('fx@1'), $FX('fx@2'), ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['SOR#095']]]);
SwuPgnTestEq([count($s['effects']), $s['effects'][0]['targets'], $s['effects'][1]['targets']], [2, ['SOR#096'], ['SOR#095', 'SOR#096']], '§12.2 EFFECT_END with targets: only those leave that effect');
$s = F([$FX('fx@1'), ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['SOR#095']], ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['SOR#096']]]);
SwuPgnTestCheck(($s['effects'] ?? null) === [] && strpos(SwuPgnStateToJson($s), '"effects":[]') !== false, '§12.2 EFFECT_END: an effect left with no targets is removed; effects stays (empty) once created');
$s = F([$FX('fx@1'), $FX('fx@2'), ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired'], ['t' => 'EFFECT_END', 'id' => 'fx@2', 'reason' => 'expired', 'targets' => []]]);
SwuPgnTestEq($s['effects'], [], '§12.2 EFFECT_END without targets (or empty) removes the effect');
$s = F([$FX('fx@1', ['seat' => 1, 'targets' => null]), ['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'targetLeft', 'targets' => ['SOR#095']], ['t' => 'EFFECT_END', 'id' => 'fx@9', 'reason' => 'expired']]);
SwuPgnTestEq(array_column($s['effects'], 'id'), ['fx@1'], '§12.2 EFFECT_END: an effect with no targets is left as it is by a partial end; an unknown id does nothing');
$s = F([['t' => 'EFFECT_END', 'id' => 'fx@1', 'reason' => 'expired']]);
SwuPgnTestCheck(!array_key_exists('effects', $s), '§11: an EFFECT_END alone does not create effects');

// §10.1 / §12.2 STATS attributes
$s = F([['t' => 'STATS', 'card' => 'SOR#095', 'power' => 3, 'hp' => 3, 'keywords' => ['sentinel'], 'traits' => ['trooper', 'clone', 'rebel'], 'blank' => true, 'leaderUnit' => true,
    'copyOf' => 'SOR#095:2', 'name' => 'Battlefield Marine', 'cost' => 2, 'aspects' => ['heroism', 'command']]], $kfHost);
$c = C($s, 'SOR#095');
SwuPgnTestEq([$c['traits'] ?? null, $c['blank'] ?? null, $c['leaderUnit'] ?? null, $c['copyOf'] ?? null, $c['name'] ?? null, $c['cost'] ?? null, $c['aspects'] ?? null],
    [['clone', 'rebel', 'trooper'], true, true, 'SOR#095:2', 'Battlefield Marine', 2, ['heroism', 'command']], '§12.2 STATS: traits (sorted), blank, leaderUnit, copyOf, name, cost, aspects set as stated');
$s = F([['t' => 'STATS', 'card' => 'SOR#095', 'power' => 3, 'hp' => 3, 'keywords' => ['sentinel'], 'traits' => ['rebel'], 'blank' => true, 'copyOf' => 'SOR#095:2', 'name' => 'X', 'cost' => 2, 'aspects' => ['command']],
    ['t' => 'STATS', 'card' => 'SOR#095', 'power' => 2, 'hp' => 2, 'blank' => false, 'leaderUnit' => false]], $kfHost);
$c = C($s, 'SOR#095');
SwuPgnTestEq([$c['power'], $c['keywords'] ?? null, array_values(array_intersect(array_keys($c), ['traits', 'blank', 'leaderUnit', 'copyOf', 'name', 'cost', 'aspects']))], [2, ['sentinel'], []],
    '§12.2 STATS: the seven are stated all at once — each one left out is removed; blank/leaderUnit kept only when true; keywords only when given');

// §12.1 step 2: into resource with exhausted:true; out of the row leaves foreignResources.
$s = F([M('SOR#010', 'hand', 'resource', 1, ['exhausted' => true]), M('SOR#011', 'hand', 'resource', 1, ['exhausted' => false])], SwuPgnTestKeyframe());
SwuPgnTestEq([P($s, 1)['resourcesReady'], P($s, 1)['resourcesExhausted']], [1, 1], '§12.1.2: a move into resource with exhausted:true counts in resourcesExhausted');
$kfFr = SwuPgnTestKeyframe(['resources' => ['R#9', 'A#1'], 'foreignResources' => ['R#9'], 'resourcesReady' => 2], ['resources' => ['R#8', 'R#7'], 'foreignResources' => ['R#7'], 'resourcesReady' => 2, 'hand' => ['H#2'], 'handSize' => 1, 'discard' => ['D#2']]);
$s = F([M('R#9', 'resource', 'discard', 2)], $kfFr);
SwuPgnTestEq([P($s, 1)['resources'], P($s, 1)['foreignResources'], P($s, 1)['resourcesReady'], P($s, 2)['resourcesReady'], P($s, 2)['discard']], [['A#1'], [], 1, 2, ['D#2', 'R#9']],
    '§12.1 whose pile + 2: a card leaving the row is taken (with the count) from the seat whose row holds it, and leaves its foreignResources; it arrives in p\'s pile');
// §12.1 whose pile + step 3 owner.
$s = F([M('R#9', 'resource', 'ground', 1)], $kfFr);
SwuPgnTestEq([C($s, 'R#9')['_seat'] ?? null, C($s, 'R#9')['owner'] ?? null, P($s, 1)['foreignResources']], [1, 2, []], '§12.1.3: into play from p\'s own row where it was one of p\'s foreignResources → owner is the other seat');
$s = F([M('R#8', 'resource', 'ground', 1)], $kfFr);
SwuPgnTestEq([C($s, 'R#8')['_seat'] ?? null, C($s, 'R#8')['owner'] ?? null, P($s, 2)['resources'], P($s, 2)['resourcesReady']], [1, 2, ['R#7'], 1], '§12.1.3: into play from the other seat\'s row (not one of its foreignResources) → owner is that seat; taken from its row');
$s = F([M('R#7', 'resource', 'ground', 1)], $kfFr);
SwuPgnTestEq([C($s, 'R#7')['_seat'] ?? null, array_key_exists('owner', C($s, 'R#7') ?? []), P($s, 2)['foreignResources']], [1, false, []], '§12.1.3: … one of the other seat\'s foreignResources (p owns it) gets no owner');
$s = F([M('H#2', 'hand', 'ground', 1), M('D#2', 'discard', 'space', 1)], $kfFr);
SwuPgnTestEq([C($s, 'H#2')['owner'] ?? null, C($s, 'D#2')['owner'] ?? null, P($s, 2)['hand'], P($s, 2)['handSize'], P($s, 2)['discard'], P($s, 1)['handSize']], [2, 2, [], 0, [], 0],
    '§12.1 whose pile: leaving the other seat\'s hand / discard takes it from there (handSize too); entering play from it → owner is that seat');
$s = F([M('SOR#200', 'hand', 'ground', 1), M('SOR#300', 'deck', 'ground', 1)], $kfHost);
SwuPgnTestCheck(!array_key_exists('owner', C($s, 'SOR#200')) && !array_key_exists('owner', C($s, 'SOR#300')), '§12.1.3: entering play from p\'s own hand or deck sets no owner');
$s = F([M('SOR#200', 'hand', 'ground', 1)], SwuPgnTestKeyframe(['hand' => ['SOR#200'], 'handSize' => 1], ['hand' => ['SOR#200'], 'handSize' => 1]));
SwuPgnTestEq([P($s, 1)['handSize'], P($s, 2)['handSize'], array_key_exists('owner', C($s, 'SOR#200'))], [0, 1, false], '§12.1 whose pile: p\'s own list is looked at first');
$s = F([M('SOR#201', 'hand', 'discard', 2)], SwuPgnTestKeyframe(['handSize' => 2], ['handSize' => 1]));
SwuPgnTestEq([P($s, 1)['handSize'], P($s, 2)['handSize']], [2, 0], '§12.1 whose pile: a hidden card (in no seat\'s hand list) leaves p\'s hand');

// §12.1 step 1c: base ↔ arena by the leader's id.
$s = F([M('SOR#010', 'base', 'ground', 1)], $kfL);
SwuPgnTestEq([P($s, 1)['leader']['deployed'], C($s, 'SOR#010')['zone'] ?? null], [true, 'ground'], '§12.1.1c mirror: the leader\'s base → arena move deploys it, with or without a DEPLOY_LEADER');
$s = F([M('TWI#017', 'base', 'space', 2), M('TWI#017', 'space', 'base', 2)], $kfL);
SwuPgnTestEq([P($s, 2)['leader']['deployed'], C($s, 'TWI#017')], [false, null], '§12.1.1c: … and its arena → base move undeploys it');

// upgradeControllers (§11, §12.1 step 3, §12.2 PLAY_UPGRADE / DEPLOY_LEADER).
$kfUC = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')], 'leader' => ['id' => 'SOR#010', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false]],
    ['cards' => [SwuPgnTestCard('SOR#045', 'space')], 'leader' => ['id' => 'JTL#002', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false]]);
$s = F([M('LOF#215', 'hand', 'ground', 2, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095']), ['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'LOF#215', 'target' => 'SOR#095', 'zone' => 'ground']], $kfUC);
SwuPgnTestEq(C($s, 'SOR#095')['upgradeControllers'] ?? null, ['LOF#215' => 2], '§12.1.3: an attaching move by the other seat notes its controller on the host');
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'LOF#216', 'target' => 'SOR#095'], ['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#217', 'target' => 'SOR#095']], $kfUC);
SwuPgnTestEq(C($s, 'SOR#095')['upgradeControllers'] ?? null, ['LOF#216' => 2], '§12.2 PLAY_UPGRADE notes p as controller (as step 3); the host\'s own seat gets no entry');
$s = F([['t' => 'DEPLOY_LEADER', 'p' => 2, 'card' => 'JTL#002', 'kind' => 'upgrade', 'target' => 'SOR#095', 'zone' => 'ground']], $kfUC);
SwuPgnTestEq([C($s, 'SOR#095')['upgrades'], C($s, 'SOR#095')['upgradeControllers'] ?? null], [['JTL#002'], ['JTL#002' => 2]], '§12.2 DEPLOY_LEADER as a pilot notes p as controller');
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'LOF#216', 'target' => 'SOR#095'], M('LOF#216', 'ground', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095'])], $kfUC);
SwuPgnTestCheck(!array_key_exists('upgradeControllers', C($s, 'SOR#095')) && strpos(SwuPgnStateToJson($s), 'upgradeControllers') === false, '§12.1.3: a rebinding by the holder removes the entry; a host left with none has no upgradeControllers (never emitted empty)');
$s = F([], SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgradeControllers' => new stdClass()])]]));
SwuPgnTestCheck(strpos(SwuPgnStateToJson($s), 'upgradeControllers') === false, 'SwuPgnStateToJson: a snapped empty upgradeControllers {} is never emitted');
$s = F([], SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgrades' => ['U#1'], 'upgradeControllers' => ['U#1' => 2]])]]));
SwuPgnTestCheck(strpos(SwuPgnStateToJson($s), '"upgradeControllers":{"U#1":2}') !== false, 'SwuPgnStateToJson: a non-empty upgradeControllers is an object');
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'LOF#216', 'target' => 'SOR#095'], M('LOF#216', 'ground', 'discard', 2, ['kind' => 'upgrade'])], $kfUC);
SwuPgnTestCheck(!array_key_exists('upgradeControllers', C($s, 'SOR#095')), '§12.2 detach(): an exit removes the upgrade from upgradeControllers too');
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'LOF#216', 'target' => 'SOR#095'], M('LOF#216', 'ground', 'space', 2, ['kind' => 'upgrade', 'attachedTo' => 'SOR#045'])], $kfUC);
SwuPgnTestEq([C($s, 'SOR#095')['upgrades'], array_key_exists('upgradeControllers', C($s, 'SOR#095')), C($s, 'SOR#045')['upgrades'], array_key_exists('upgradeControllers', C($s, 'SOR#045'))], [[], false, ['LOF#216'], false], '§12.1.3: a re-host takes the entry off the old host; p = controller after the move');
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 2, 'card' => 'constructor', 'target' => 'SOR#095'], M('__proto__', 'hand', 'ground', 2, ['kind' => 'upgrade', 'attachedTo' => 'SOR#095'])], $kfUC);
SwuPgnTestCheck(!array_key_exists('upgradeControllers', C($s, 'SOR#095')), '§23: a reserved name is never made a key of upgradeControllers');

// §10.1 TAKE_CONTROL — the arena unit form: owner + rebuilt upgradeControllers.
$kfTk = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')]],
    ['cards' => [SwuPgnTestCard('SOR#045', 'space', ['upgrades' => ['U#1', 'U#2', 'U#3'], 'upgradeControllers' => ['U#2' => 1]]), SwuPgnTestCard('SOR#046', 'ground', ['owner' => 1])]]);
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#045', 'zone' => 'space', 'from' => 2]], $kfTk);
SwuPgnTestEq([C($s, 'SOR#045')['_seat'] ?? null, C($s, 'SOR#045')['owner'] ?? null, C($s, 'SOR#045')['upgradeControllers'] ?? null], [1, 2, ['U#1' => 2, 'U#3' => 2]],
    '§10.1 TAKE_CONTROL arena: owner = the seat it came from; upgradeControllers rebuilt against p (p\'s own upgrade loses its entry, the old seat\'s gain one)');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#046', 'zone' => 'ground', 'from' => 2]], $kfTk);
SwuPgnTestCheck((C($s, 'SOR#046')['_seat'] ?? null) === 1 && !array_key_exists('owner', C($s, 'SOR#046')) && !array_key_exists('upgradeControllers', C($s, 'SOR#046')), '§10.1 TAKE_CONTROL arena: going home removes owner; no upgrades → no upgradeControllers');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#045', 'zone' => 'space', 'from' => 2], ['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'SOR#045', 'zone' => 'space', 'from' => 1]], $kfTk);
SwuPgnTestEq([C($s, 'SOR#045')['_seat'] ?? null, array_key_exists('owner', C($s, 'SOR#045')), C($s, 'SOR#045')['upgradeControllers'] ?? null], [2, false, ['U#2' => 1]], '§10.1 TAKE_CONTROL arena: back and forth restores the original owner and controllers');
// The upgrade form.
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'U#1', 'zone' => 'space', 'from' => 2], ['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'U#2', 'zone' => 'space']], $kfTk);
SwuPgnTestEq([C($s, 'SOR#045')['_seat'] ?? null, C($s, 'SOR#045')['upgrades'], C($s, 'SOR#045')['upgradeControllers'] ?? null], [2, ['U#1', 'U#2', 'U#3'], ['U#1' => 1]], '§10.1 TAKE_CONTROL upgrade form: nothing moves; the host\'s entry is set to p, or removed when p holds the host');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'U#2', 'zone' => 'space']], $kfTk);
SwuPgnTestCheck(!array_key_exists('upgradeControllers', C($s, 'SOR#045')), '§10.1 TAKE_CONTROL upgrade form: a host left with no entry has no upgradeControllers');
// The resource forms.
$kfTr = SwuPgnTestKeyframe(['resources' => ['A#1'], 'resourcesReady' => 1, 'foreignResources' => ['B#1']], ['resources' => ['B#2', 'B#9'], 'resourcesReady' => 2, 'foreignResources' => ['A#9']]);
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'B#2', 'zone' => 'resource', 'from' => 2], ['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'A#9', 'zone' => 'resource', 'from' => 2]], $kfTr);
SwuPgnTestEq([P($s, 1)['resources'], P($s, 1)['foreignResources'], P($s, 2)['resources'], P($s, 2)['foreignResources'], P($s, 1)['resourcesReady']], [['A#1', 'B#2', 'A#9'], ['B#1', 'B#2'], ['B#9'], [], 3],
    '§10.1 TAKE_CONTROL resource+from: a stolen card joins p\'s foreignResources; one in from\'s foreignResources is going home and leaves it');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'B#2', 'zone' => 'resource', 'from' => 2]], SwuPgnTestKeyframe([], ['resources' => ['B#2'], 'resourcesReady' => 1]));
SwuPgnTestEq(P($s, 1)['foreignResources'] ?? null, ['B#2'], '§10.1: foreignResources is created by the first resource TAKE_CONTROL');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'A#9', 'zone' => 'resource', 'owner' => 1], ['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'C#1', 'zone' => 'resource', 'owner' => 2]], $kfTr);
SwuPgnTestEq([P($s, 1)['foreignResources'], P($s, 2)['foreignResources'], P($s, 1)['resourcesReady'], P($s, 2)['resourcesReady']], [['B#1', 'C#1'], [], 1, 2],
    '§10.1 TAKE_CONTROL resource without from: no counts; out of the other seat\'s foreignResources; owner ≠ p → into p\'s');
$s = F([['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'B#1', 'zone' => 'resource', 'owner' => 1], ['t' => 'TAKE_CONTROL', 'p' => 2, 'card' => 'Z#1', 'zone' => 'resource', 'owner' => 1]], $kfTr);
SwuPgnTestEq([P($s, 1)['foreignResources'], P($s, 2)['foreignResources']], [[], ['A#9', 'Z#1']], '§10.1 TAKE_CONTROL resource without from: owner = p takes it out of p\'s foreignResources');

// §12.2 PLAY_EVENT only when no seat's discard holds it; CAPTURE of a token.
$s = F([M('SOR#150', 'hand', 'discard', 2), ['t' => 'PLAY_EVENT', 'p' => 1, 'card' => 'SOR#150']]);
SwuPgnTestEq([P($s, 1)['discard'], P($s, 2)['discard']], [[], ['SOR#150']], '§12.2 PLAY_EVENT adds nothing when another seat\'s discard already holds the card');
$kfTok = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095')]], ['cards' => [SwuPgnTestCard('TOKEN:battle-droid#3463348370')]]);
$s = F([['t' => 'CAPTURE', 'p' => 1, 'card' => 'TOKEN:battle-droid#3463348370', 'by' => 'SOR#095'], ['t' => 'CAPTURE', 'p' => 1, 'card' => 'TOKEN:spy#6665455613', 'by' => 'base@1']], $kfTok);
SwuPgnTestEq([P($s, 2)['cards'], C($s, 'SOR#095')['captured'], array_key_exists('baseCaptured', P($s, 1))], [[], [], false], '§12.2 CAPTURE of a TOKEN: id: off the board, nothing more');

// §23 reserved names: a STATUS_TOKEN with one is never applied.
// Reduced directly: a parser rejects such a file outright (`reserved-key`), so this is "given events some other way".
$s = F([], $kfHost);
foreach (['toString', 'prototype', '__proto__'] as $tok) $s = SwuPgnReduce($s, ['seq' => 'R1.A.9', 't' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => $tok, 'count' => 1]);
SwuPgnTestEq(C($s, 'SOR#095')['statusTokens'], new stdClass(), '§23: a STATUS_TOKEN whose token is a reserved name is not applied');

SwuPgnTestFinish();
