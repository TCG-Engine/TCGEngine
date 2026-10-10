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
$kfOld = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgrades' => null, 'captured' => null, 'statusTokens' => null])]]);
$s = F([['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'target' => 'SOR#095'], ['t' => 'STATUS_TOKEN', 'card' => 'SOR#095', 'token' => 'advantage', 'count' => 1]], $kfOld);
SwuPgnTestEq([C($s, 'SOR#095')['upgrades'], C($s, 'SOR#095')['statusTokens']], [['LOF#215'], ['advantage' => 1]], '§11: a keyframe card without upgrades/statusTokens (older file) still takes them');

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
SwuPgnTestEq([$e['round'], $e['phase'], $e['initiative'], array_keys($e['players']), $e['players'][1]['baseHp'], array_key_exists('initiativeTaken', $e)], [0, 'setup', null, [1, 2], 30, false], '§11 emptyState(): round 0, setup, no initiative, both seats at the 30 placeholder');

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
SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson(SwuPgnTestKeyframe(['cards' => [new stdClass()]]))) === null, '§13: an empty-object card entry is still an object');
SwuPgnTestCheck(SwuPgnKeyframeProblem(SwuPgnTestViaJson($kf)) === null, 'SwuPgnKeyframeProblem: a complete keyframe has no problem');
$s = F([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => SwuPgnTestKeyframe([], [], ['round' => 1])], ['t' => 'ROUND_START', 'round' => 2]]);
SwuPgnTestEq([$s['round'], $s['initiativeTaken']], [2, false], 'a ROUND_START without a keyframe just keeps folding (valid and harmless)');

SwuPgnTestFinish();
