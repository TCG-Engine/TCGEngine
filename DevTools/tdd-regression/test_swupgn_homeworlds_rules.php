<?php
// SWU-PGN/1.0 (Homeworlds revision, 2026-10-26) — the reader's side of §10.3 / §10.4 and the rules that came with them:
// a base defeated with a DEFEAT on base@N (baseDefeated, even with HP left), an attachment rebound inside its arena (a
// MOVE whose from = to), base attachments as their own fold step (2c), sources that may be omitted but never invented,
// a card with two names (backSideName), grantedBy, WriterRevision, and §9.1's run rules for action abilities and arrivals.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_homeworlds_rules.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

function HDoc(array $events, array $o = []): array { return SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($events), $o)); }
function HF(array $events, ?array $kf = null): array
{
    if ($kf !== null) array_unshift($events, ['seq' => 'R1.start', 't' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf]);
    return SwuPgnFold(HDoc($events)['events']);
}
function HP(array $s, int $seat): array { return $s['players'][$seat]; }
function HM(string $card, string $from, string $to, ?int $p = 1, array $x = []): array
{
    $e = ['t' => 'MOVE', 'card' => $card, 'from' => $from, 'to' => $to];
    if ($p !== null) $e['p'] = $p;
    return $e + $x;
}
function HCodes(array $events, array $o = []): array
{
    $v = SwuPgnValidate(HDoc($events, $o));
    return array_values(array_unique(array_filter(array_column(array_merge($v['errors'], $v['warnings']), 'code'), fn($c) => $c !== 'section.missing')));
}
function HErrors(array $events, array $o = []): array
{
    return array_values(array_unique(array_column(SwuPgnValidate(HDoc($events, $o))['errors'], 'code')));
}

// ── §10.4: a base defeated outright keeps its HP; baseDefeated says it fell ─────────────────────
$kf = SwuPgnTestKeyframe([], ['baseHp' => 9]);
$s = HF([['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'ability', 'defeatedBy' => 'HMW#004']], $kf);
SwuPgnTestEq([HP($s, 2)['baseHp'], HP($s, 2)['baseDefeated'] ?? null, HP($s, 2)['discard'], array_key_exists('baseDefeated', HP($s, 1))],
    [9, true, [], false], '§12.2 DEFEAT base@2: baseDefeated, the HP untouched, nothing filed anywhere; the other seat untouched');
$s = HF([['t' => 'DAMAGE', 'src' => 'SOR#095', 'tgt' => 'base@2', 'amt' => 9, 'damageType' => 'combat', 'hp' => 0]], $kf);
SwuPgnTestCheck(!array_key_exists('baseDefeated', HP($s, 2)), 'baseDefeated is written only by a DEFEAT — 0 HP alone does not set it (absent = false)');

// §14: compared, absent read as false on either side.
$chk = fn(array $events) => SwuPgnCheckKeyframes(HDoc($events)['events']);
$kf0 = SwuPgnTestKeyframe();
$end = fn(array $k) => ['t' => 'ROUND_END', 'round' => 1, 'keyframe' => $k];
$r = $chk([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf0], ['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'ability'],
    $end(SwuPgnTestKeyframe([], ['baseDefeated' => true]))]);
SwuPgnTestEq($r['mismatches'], [], '§14: baseDefeated that the deltas built matches');
$r = $chk([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf0], $end(SwuPgnTestKeyframe([], ['baseDefeated' => true]))]);
SwuPgnTestEq(array_column($r['mismatches'], 'path'), ['players.2.baseDefeated'], '§14: a baseDefeated no DEFEAT built is a mismatch');
$r = $chk([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf0], ['t' => 'DEFEAT', 'card' => 'base@1', 'reason' => 'attack'], $end(SwuPgnTestKeyframe())]);
SwuPgnTestEq(array_column($r['mismatches'], 'path'), ['players.1.baseDefeated'], '§14: a keyframe without it reads false — a fold that defeated the base disagrees');

// ── §10.1 the binding rule: an attachment rebound inside its arena (from = to) ───────────────────
$kfHosts = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#095', 'ground', ['upgrades' => ['SOR#120']]), SwuPgnTestCard('SOR#046')]]);
$s = HF([HM('SOR#120', 'ground', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#046'])], $kfHosts);
SwuPgnTestEq(array_map(fn($c) => [$c['id'], $c['upgrades']], HP($s, 1)['cards']), [['SOR#095', []], ['SOR#046', ['SOR#120']]],
    '§12.1 step 3: an upgrade moved to another host in its arena leaves the old host and joins the new one');
$kfPilot = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('JTL#081', 'ground', ['upgrades' => ['JTL#046']])]]);
$s = HF([HM('JTL#046', 'ground', 'ground', 1, ['kind' => 'unit'])], $kfPilot);
SwuPgnTestEq(array_map(fn($c) => [$c['id'], $c['zone'], $c['upgrades']], HP($s, 1)['cards']), [['JTL#081', 'ground', []], ['JTL#046', 'ground', []]],
    '§12.1 step 3: a pilot ejected in its arena (kind unit, no host) is nobody\'s upgrade and a unit of its own');
$s = HF([HM('JTL#046', 'space', 'ground', 1, ['kind' => 'unit'])], SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('JTL#081', 'space', ['upgrades' => ['JTL#046']])]]));
SwuPgnTestEq(array_map(fn($c) => [$c['id'], $c['upgrades']], HP($s, 1)['cards']), [['JTL#081', []], ['JTL#046', []]],
    '§12.1 step 3: moving INTO an arena detaches first — ejected from a space Vehicle to the ground');
$kfUnit = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('JTL#046'), SwuPgnTestCard('JTL#081', 'space')]]);
$s = HF([HM('JTL#046', 'ground', 'space', 1, ['kind' => 'upgrade', 'attachedTo' => 'JTL#081'])], $kfUnit);
SwuPgnTestEq(array_map(fn($c) => [$c['id'], $c['upgrades']], HP($s, 1)['cards']), [['JTL#081', ['JTL#046']]],
    '§12.1 step 3: a unit attached as a pilot stops being a unit — out of cards, onto its host');
$s = HF([HM('SOR#095', 'hand', 'hand', 1)], SwuPgnTestKeyframe(['hand' => ['SOR#095'], 'handSize' => 1]));
SwuPgnTestEq(HP($s, 1)['handSize'], 1, 'a same-zone MOVE outside the arenas is still non-conformant and ignored');
SwuPgnTestEq(HErrors([HM('SOR#120', 'ground', 'ground', 1, ['kind' => 'upgrade', 'attachedTo' => 'SOR#046'])]), [], 'validate: a rebind in its arena is legal');
SwuPgnTestEq(HErrors([HM('JTL#046', 'space', 'space', 1, ['kind' => 'unit'])]), [], 'validate: an ejection in its arena is legal');
SwuPgnTestCheck(in_array('same-zone-move', HErrors([HM('SOR#095', 'hand', 'hand')]), true), 'validate: from = to anywhere else is still an error');
SwuPgnTestCheck(!in_array('same-zone-move', HErrors([HM('SOR#095', 'ground', 'ground')]), true), '§24 (10-28): same-zone-move is a MOVE from a zone to itself that is NOT an arena — a kind-less same-arena MOVE is not one (the fold still ignores it, below)');
$s = HF([HM('JTL#046', 'ground', 'ground')], $kfPilot);
SwuPgnTestEq(array_map(fn($c) => [$c['id'], $c['upgrades']], HP($s, 1)['cards']), [['JTL#081', ['JTL#046']]], '…and the fold ignores it like any non-conformant MOVE (the pilot stays on its host)');
SwuPgnTestCheck(in_array('schema', HErrors([HM('SOR#120', 'ground', 'ground', 1, ['kind' => 'upgrade'])]), true), 'validate: a rebind onto a host names it');

// ── §12.1 step 2c: base attachments ─────────────────────────────────────────────────────────────
$kfH = SwuPgnTestKeyframe([], ['hand' => ['HMW#206'], 'handSize' => 1]);
$s = HF([HM('HMW#206', 'hand', 'base', 2, ['kind' => 'upgrade'])], $kfH);
SwuPgnTestEq(HP($s, 2)['baseUpgrades'] ?? null, ['HMW#206'], '2c: a file that predates attachedTo on base moves attaches to p\'s base');
$s = HF([HM('HMW#206', 'hand', 'base', null, ['kind' => 'upgrade', 'attachedTo' => 'base@2'])], $kfH);
SwuPgnTestEq(HP($s, 2)['baseUpgrades'] ?? null, ['HMW#206'], '2c needs no p when attachedTo names the base');
$s = HF([HM('HMW#206', 'base', 'discard', null, ['kind' => 'upgrade'])], SwuPgnTestKeyframe([], ['baseUpgrades' => ['HMW#206']]));
SwuPgnTestEq(HP($s, 2)['baseUpgrades'], [], '2c: a MOVE out of base needs no p to take it off the base');
$s = HF([HM('TOKEN:shield#8752877738', 'outsideTheGame', 'base', 2, ['kind' => 'upgrade', 'attachedTo' => 'base@2'])], $kfH);
SwuPgnTestCheck(!array_key_exists('baseUpgrades', HP($s, 2)), '2c: a TOKEN: id is never added to baseUpgrades');
$s = HF([HM('HMW#206', 'hand', 'base', 2, ['kind' => 'upgrade', 'attachedTo' => 'base@1'])], $kfH);
SwuPgnTestEq([HP($s, 1)['baseUpgrades'] ?? null, HP($s, 2)['handSize'], array_key_exists('baseUpgrades', HP($s, 2))], [['HMW#206'], 0, false],
    '2c: the seat attachedTo names is the base it is on; the MOVE\'s p is the owner whose hand it left');

// ── sources: optional, but only ever a card or base@N ───────────────────────────────────────────
$dmg = fn(array $x) => ['t' => 'DAMAGE', 'tgt' => 'base@1', 'amt' => 1, 'damageType' => 'ability', 'hp' => 29] + $x;
SwuPgnTestEq(HErrors([$dmg([])]), [], 'validate: DAMAGE without src (the rules dealt it) is legal');
foreach (['Framework effect', 'Framework effect:2', 'unknown', 'rule:deck-out'] as $bad) {
    SwuPgnTestCheck(in_array('schema', HErrors([$dmg(['src' => $bad])]), true), "validate: DAMAGE.src \"$bad\" is not a card id or base@N");
}
SwuPgnTestCheck(in_array('schema', HErrors([['t' => 'HEAL', 'src' => 'unknown', 'tgt' => 'base@1', 'amt' => 1, 'hp' => 30]]), true), 'validate: HEAL.src too');
SwuPgnTestCheck(in_array('schema', HErrors([['t' => 'DEFEAT', 'card' => 'SOR#095', 'reason' => 'ability', 'defeatedBy' => 'unknown']]), true), 'validate: DEFEAT.defeatedBy too');
foreach (['SOR#095', 'SOR#095:2', 'base@2', 'TOKEN:beast#6445363199:3', 'TOKEN:beast'] as $ok) {
    SwuPgnTestEq(HErrors([$dmg(['src' => $ok])]), [], "validate: DAMAGE.src \"$ok\" is a card id or base@N");
}

// ── grantedBy, backSideName, WriterRevision, DRAW.count, keyframe baseDefeated ───────────────────
$abil = ['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'base@1', 'kind' => 'action', 'grantedBy' => 'HMW#172'];
SwuPgnTestEq(HErrors([$abil]), [], 'validate: ABILITY_ACTIVATE on base@1 granted by an upgrade');
SwuPgnTestCheck(in_array('schema', HErrors([['grantedBy' => 7] + $abil]), true), 'validate: grantedBy is a string');
SwuPgnTestCheck(in_array('schema', HErrors([['grantedBy' => 'base@3'] + $abil]), true), 'validate: grantedBy base@N names seat 1 or 2');
$tarkin = ['id' => 'HMW#004', 'name' => 'Grand Moff Tarkin, Tyrant of the Outer Rim', 'backSideName' => 'The Death Star, Icon of Tyranny'];
SwuPgnTestEq(HErrors([], ['cards' => [$tarkin]]), [], 'validate: a CARDS entry with a backSideName');
SwuPgnTestCheck(in_array('schema', HErrors([], ['cards' => [['backSideName' => 4] + $tarkin]]), true), 'validate: backSideName is a string');
SwuPgnTestEq(HErrors([], ['header' => ['WriterRevision' => '1']]), [], 'validate: [WriterRevision "1"]');
SwuPgnTestEq(SwuPgnTryParse(SwuPgnTestFile([], ['header' => ['WriterRevision' => 'r1']]))['code'] ?? null, 'bad-count', '§5.2/§24: a [WriterRevision] that is not digits makes the file malformed (bad-count)');
SwuPgnTestEq(HErrors([['t' => 'DRAW', 'p' => 1, 'count' => 2, 'cards' => ['SOR#095']]]), [], '§24 (10-28): a DRAW whose count is not its cards is no §24 error (the old draw.count warning has no code now)');
SwuPgnTestEq(HCodes([['t' => 'DRAW', 'p' => 2, 'count' => 2, 'cards' => []]]), [], 'validate: a hidden DRAW (no cards) may count any number');
SwuPgnTestCheck(in_array('schema', HErrors([['t' => 'ROUND_START', 'round' => 1, 'active' => 1, 'keyframe' => SwuPgnTestKeyframe([], ['baseDefeated' => 'yes'])]]), true),
    'validate: a keyframe baseDefeated is a boolean');

// ── §16 the story: two names, the deploy as a sub-step, grantedBy, the base line ─────────────────
$cards = [$tarkin, ['id' => 'SOR#095', 'name' => 'Wampa', 'kind' => 'unit'], ['id' => 'HMW#172', 'name' => 'Heavy Ion Cannon', 'kind' => 'upgrade'],
    ['id' => 'HMW#147', 'name' => 'Beast Lair', 'kind' => 'upgrade'],
    ['id' => 'TWI#017', 'name' => 'Chancellor Palpatine, Playing Both Sides', 'backSideName' => 'Darth Sidious, Playing Both Sides']];
$deploy = [
    ['seq' => 'R8.A.1', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'HMW#004', 'kind' => 'epic', 'epic' => true],
    ['seq' => 'R8.A.1a', 't' => 'MOVE', 'card' => 'HMW#004', 'from' => 'base', 'to' => 'space', 'p' => 1, 'kind' => 'unit'],
    ['seq' => 'R8.A.1b', 't' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'HMW#004', 'zone' => 'space', 'epic' => true, 'cost' => 9],
    ['seq' => 'R8.A.2', 't' => 'ATTACK', 'p' => 1, 'atk' => 'HMW#004', 'def' => 'base@2', 'defenderType' => 'base'],
    ['seq' => 'R8.G.1', 't' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'ability', 'defeatedBy' => 'HMW#004'],
    ['seq' => 'R8.G.2', 't' => 'MOVE', 'card' => 'HMW#004', 'from' => 'space', 'to' => 'base', 'p' => 1],
    ['seq' => 'R8.G.3', 't' => 'TRIGGER', 'card' => 'HMW#004', 'p' => 1],
];
$story = explode("\n", SwuPgnRender(HDoc($deploy, ['cards' => $cards])));
SwuPgnTestEq($story, [
    "  1. Player 1 uses Grand Moff Tarkin, Tyrant of the Outer Rim's Epic Action",
    '       ↳ Player 1 deploys Grand Moff Tarkin, Tyrant of the Outer Rim as The Death Star, Icon of Tyranny',
    "  2. Player 1 attacks Player 2's base with The Death Star, Icon of Tyranny",
    "       ↳ Player 2's base is defeated by The Death Star, Icon of Tyranny",
    '       ↳ Grand Moff Tarkin, Tyrant of the Outer Rim triggers',
], '§16: the Epic Action is the one numbered action, the deploy its sub-step; the side that is up names the card until it goes home');
$story = SwuPgnRender(HDoc([['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'HMW#004', 'zone' => 'space', 'epic' => true]], ['cards' => $cards]));
SwuPgnTestEq($story, '  1. Player 1 deploys Grand Moff Tarkin, Tyrant of the Outer Rim as The Death Star, Icon of Tyranny',
    '§16: a deploy on a step of its own is still a numbered action');
$flip = [['t' => 'LEADER_FLIP', 'p' => 1, 'card' => 'TWI#017', 'onStartingSide' => false], ['t' => 'TRIGGER', 'card' => 'TWI#017'],
    ['t' => 'LEADER_FLIP', 'p' => 1, 'card' => 'TWI#017', 'onStartingSide' => true]];
SwuPgnTestEq(explode("\n", SwuPgnRender(HDoc($flip, ['cards' => $cards]))), [
    '       ↳ Player 1 flips Chancellor Palpatine, Playing Both Sides to Darth Sidious, Playing Both Sides',
    '       ↳ Darth Sidious, Playing Both Sides triggers',
    '       ↳ Player 1 flips Darth Sidious, Playing Both Sides to Chancellor Palpatine, Playing Both Sides',
], '§16: LEADER_FLIP names the face it landed on; the face that is up names it after');
$granted = [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'base@1', 'kind' => 'action', 'grantedBy' => 'HMW#172'],
    ['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'base@1', 'kind' => 'triggered', 'grantedBy' => 'HMW#147']];
SwuPgnTestEq(explode("\n", SwuPgnRender(HDoc($granted, ['cards' => $cards]))), [
    "  1. Player 1 uses Player 1's base (granted by Heavy Ion Cannon)",
    "       ↳ Player 1's base uses an ability (granted by Beast Lair)",
], '§16: " (granted by …)" on either kind');
$kfBoard = SwuPgnTestKeyframe(
    ['baseUpgrades' => ['HMW#147'], 'baseCaptured' => ['SOR#095'], 'leader' => ['id' => 'HMW#004', 'deployed' => true, 'exhausted' => false, 'epicActionUsed' => true],
        'cards' => [SwuPgnTestCard('HMW#004', 'space', ['power' => 2, 'hp' => 12])]]);
$story = explode("\n", SwuPgnRender(HDoc([['seq' => 'R9.start', 't' => 'ROUND_START', 'round' => 9, 'keyframe' => $kfBoard],
    ['seq' => 'R9.A.1', 't' => 'ATTACK', 'p' => 1, 'atk' => 'HMW#004', 'def' => 'base@2', 'defenderType' => 'base']], ['cards' => $cards])));
SwuPgnTestEq(array_slice($story, 3, 3), [
    ' P1  base 30/30   hand 0   resources 0/0   leader deployed',
    '      base: Beast Lair  ·  holds Wampa',
    '      space: The Death Star, Icon of Tyranny 2/12',
], '§16 banner: a "base:" line before the arenas; a keyframe re-syncs which side is up');
SwuPgnTestEq(end($story), "  1. Player 1 attacks Player 2's base with The Death Star, Icon of Tyranny", '§16: …and the records after it use that side');

// ── §9.1 runs (the step list): an action ability's precursors, and another card's arrival ────────
$lines = SwuPgnSteps(HDoc([
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'],
    ['seq' => 'R1.A.0a', 't' => 'EXHAUST', 'card' => 'HMW#004'],
    ['seq' => 'R1.A.0b', 't' => 'STATS', 'card' => 'HMW#004', 'power' => 2, 'hp' => 12],
    ['seq' => 'R1.A.1', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'HMW#004', 'kind' => 'action'],
], ['cards' => $cards]))['steps'];
SwuPgnTestEq(array_map(fn($s) => $s['pos'], $lines), [2, 3], '§9.1: an action ability is preceded only by CHOICE / MODAL_CHOICE — its own card\'s EXHAUST before it is not its run');
$arrival = SwuPgnSteps(HDoc([
    ['seq' => 'R1.A.start', 't' => 'PHASE_START', 'phase' => 'action'],
    ['seq' => 'R1.A.0a', 't' => 'MOVE', 'card' => 'TOKEN:beast#6445363199', 'from' => 'outsideTheGame', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
    ['seq' => 'R1.A.0b', 't' => 'STATS', 'card' => 'TOKEN:beast#6445363199', 'power' => 3, 'hp' => 3],
    ['seq' => 'R1.A.0c', 't' => 'EXHAUST', 'card' => 'TOKEN:beast#6445363199'],
    ['seq' => 'R1.A.0d', 't' => 'EXHAUST_RESOURCES', 'p' => 1, 'amount' => 2],
    ['seq' => 'R1.A.0e', 't' => 'MOVE', 'card' => 'SOR#095', 'from' => 'hand', 'to' => 'ground', 'p' => 1, 'kind' => 'unit'],
    ['seq' => 'R1.A.1', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#095', 'zone' => 'ground', 'cost' => 2],
], ['cards' => $cards]))['steps'];
SwuPgnTestEq(array_map(fn($s) => $s['pos'], $arrival), [3, 6], '§9.1: another card arriving in play ends the run — the Beast and its STATS / EXHAUST stay before the play');
$dep = SwuPgnSteps(HDoc(array_merge([['seq' => 'R8.A.start', 't' => 'PHASE_START', 'phase' => 'action']], array_slice($deploy, 0, 4)), ['cards' => $cards]));
SwuPgnTestEq(array_map(fn($s) => [$s['seq'], $s['caption']], $dep['steps']), [
    ['R8.A.start', 'Round 0 · action phase'],
    ['R8.A.1', "Player 1 uses Grand Moff Tarkin, Tyrant of the Outer Rim's Epic Action"],
    ['R8.A.2', "Player 1 attacks Player 2's base with The Death Star, Icon of Tyranny"],
], 'steps: the lettered deploy is no step of its own, and captions name the side that is up');

SwuPgnTestFinish();
