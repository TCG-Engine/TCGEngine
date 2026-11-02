<?php
// SWU-PGN/1.0 spec §16: the story — wording, numbering, indentation, the UNDO marker, the round
// banner and board summary, nm()/who().
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php DevTools/tdd-regression/test_swupgn_render.php
require_once __DIR__ . '/fixtures/swupgn_test_helpers.php';

$cards = [
    ['id' => 'SOR#108', 'name' => 'Wampa', 'kind' => 'unit'],
    ['id' => 'SOR#045', 'name' => 'Cell Block Guard', 'kind' => 'unit'],
    ['id' => 'SOR#010', 'name' => 'Luke Skywalker, Faithful Friend'],
    ['id' => 'LOF#215', 'name' => 'Ascension Cable', 'kind' => 'upgrade'],
    ['id' => 'JTL#100', 'name' => 'X-Wing Vehicle', 'kind' => 'unit'],
    ['id' => 'TOKEN:advantage#5844562972', 'name' => 'Advantage', 'kind' => 'upgrade'],
    ['id' => 'SOR#150', 'name' => 'Vanquish'],
];
function R(array $events, array $cards, ?array $names = null): string
{
    $doc = SwuPgnParse(SwuPgnTestFile(SwuPgnTestSeq($events), ['cards' => $cards]));
    return SwuPgnRender($doc, $names);
}
function L(array $e, array $cards): string { return R([$e], $cards); }

// ── §16 "The exact wording" ─────────────────────────────────────────────────────────────────────
$I = '       ↳ ';
$cases = [
    [['t' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground', 'cost' => 2], '  1. Player 1 plays Wampa to ground (cost 2)'],
    [['t' => 'PLAY', 'p' => 2, 'card' => 'SOR#108:2'], '  1. Player 2 plays Wampa #2'],
    [['t' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'cost' => 0], '  1. Player 1 plays Wampa (cost 0)'],
    [['t' => 'PLAY_SMUGGLE', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground', 'cost' => 4], '  1. Player 1 plays Wampa to ground (cost 4)'],
    [['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'zone' => 'ground', 'target' => 'SOR#108', 'cost' => 2], '  1. Player 1 plays Ascension Cable on Wampa (cost 2)'],
    [['t' => 'PLAY_UPGRADE', 'p' => 1, 'card' => 'LOF#215', 'zone' => 'ground'], '  1. Player 1 plays Ascension Cable to ground'],
    [['t' => 'PLAY_EVENT', 'p' => 2, 'card' => 'SOR#150', 'zone' => 'discard', 'cost' => 5], '  1. Player 2 plays Vanquish (cost 5)'],
    [['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010'], '  1. Player 1 deploys Luke Skywalker, Faithful Friend'],
    [['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'upgrade', 'target' => 'JTL#100'], '  1. Player 1 deploys Luke Skywalker, Faithful Friend as a pilot on X-Wing Vehicle'],
    [['t' => 'LEADER_FLIP', 'p' => 2, 'card' => 'TWI#017', 'onStartingSide' => false], $I . 'Player 2 flips TWI#017'],
    [['t' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#108', 'def' => 'base@2', 'defenderType' => 'base'], '  1. Player 1 attacks Player 2\'s base with Wampa'],
    [['t' => 'ATTACK', 'p' => 2, 'atk' => 'SOR#045', 'defenderType' => 'base'], '  1. Player 2 attacks Player 1\'s base with Cell Block Guard'],
    [['t' => 'ATTACK', 'p' => 2, 'atk' => 'SOR#045', 'def' => 'SOR#108', 'defenderType' => 'unit'], '  1. Player 2 attacks Wampa with Cell Block Guard'],
    [['t' => 'PASS', 'p' => 2], '  1. Player 2 passes'],
    [['t' => 'CLAIM_INITIATIVE', 'p' => 1], '  1. Player 1 claims initiative'],
    [['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'base@2', 'amt' => 4, 'damageType' => 'combat', 'hp' => 26], $I . '4 damage to Player 2\'s base — 26 HP left'],
    [['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'SOR#045:3', 'amt' => 4, 'damageType' => 'combat', 'hp' => 0], $I . '4 damage to Cell Block Guard #3 — 0 HP left'],
    [['t' => 'OVERWHELM', 'p' => 1, 'tgt' => 'base@2', 'amt' => 2, 'hp' => 24], $I . '2 Overwhelm damage to Player 2\'s base — 24 HP left'],
    [['t' => 'HEAL', 'src' => 'SOR#045', 'tgt' => 'base@1', 'amt' => 2, 'hp' => 30], $I . '2 healed on Player 1\'s base — 30 HP left'],
    [['t' => 'DEFEAT', 'card' => 'SOR#045', 'reason' => 'attack', 'defeatedBy' => 'SOR#108'], $I . 'Cell Block Guard is defeated by Wampa'],
    [['t' => 'DEFEAT', 'card' => 'SOR#045', 'reason' => 'frameworkEffect'], $I . 'Cell Block Guard is defeated'],
    [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#010', 'kind' => 'action'], '  1. Player 1 uses Luke Skywalker, Faithful Friend'],
    [['t' => 'ABILITY_ACTIVATE', 'p' => 2, 'card' => 'base@2', 'kind' => 'epic', 'epic' => true], '  1. Player 2 uses Player 2\'s base\'s Epic Action'],
    [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#108', 'kind' => 'triggered'], $I . 'Wampa uses an ability'],
    [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#108'], $I . 'Wampa uses an ability'],
    [['t' => 'TRIGGER', 'card' => 'SOR#108'], $I . 'Wampa triggers'],
    [['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'advantage', 'count' => 1], $I . 'Wampa gains 1 advantage'],
    [['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => 'weakness', 'count' => -2], $I . 'Wampa loses 2 weakness'],
    [['t' => 'SHIELD_GAIN', 'card' => 'SOR#108'], $I . 'Wampa gains 1 shield'],
    [['t' => 'SHIELD_GAIN', 'card' => 'SOR#108', 'count' => 2], $I . 'Wampa gains 2 shield'],
    [['t' => 'SHIELD_USE', 'card' => 'SOR#108'], $I . 'Wampa loses 1 shield'],
    [['t' => 'EXPERIENCE_GAIN', 'card' => 'SOR#108', 'count' => 1], $I . 'Wampa gains 1 experience'],
    [['t' => 'EXPERIENCE_GAIN', 'card' => 'SOR#108', 'count' => -1], $I . 'Wampa loses 1 experience'],
    [['t' => 'DRAW', 'p' => 1, 'count' => 2, 'cards' => ['SOR#108', 'SOR#108:2']], $I . 'Player 1 draws 2: Wampa · Wampa #2'],
    [['t' => 'DRAW', 'p' => 2, 'count' => 2, 'cards' => []], $I . 'Player 2 draws 2'],
    [['t' => 'DISCARD', 'p' => 1, 'cards' => ['SOR#108', 'SOR#150']], $I . 'Player 1 discards Wampa · Vanquish'],
    [['t' => 'RESOURCE', 'p' => 1, 'card' => 'SOR#108:4'], $I . 'Player 1 resources Wampa #4'],
    [['t' => 'REVEAL', 'p' => 2, 'zone' => 'hand', 'cards' => ['SOR#045']], $I . 'Player 2 reveals Cell Block Guard'],
    [['t' => 'SEARCH', 'p' => 1, 'found' => ['SOR#108'], 'zone' => 'deck'], $I . 'Player 1 searches, finds Wampa'],
    [['t' => 'SEARCH', 'p' => 1], $I . 'Player 1 searches their deck'],
    [['t' => 'CREATE_TOKEN', 'p' => 1, 'token' => 'TOKEN:advantage#5844562972:2', 'zone' => 'ground'], $I . 'Player 1 creates Advantage #2 in ground'],
    [['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045', 'by' => 'SOR#108'], $I . 'Player 1 captures Cell Block Guard with Wampa'],
    [['t' => 'CAPTURE', 'p' => 1, 'card' => 'SOR#045'], $I . 'Player 1 captures Cell Block Guard'],
    [['t' => 'RESCUE', 'p' => 2, 'card' => 'SOR#045'], $I . 'Player 2 rescues Cell Block Guard'],
    [['t' => 'TAKE_CONTROL', 'p' => 1, 'card' => 'SOR#045', 'zone' => 'ground'], $I . 'Player 1 takes control of Cell Block Guard'],
    [['t' => 'MULLIGAN', 'p' => 2], $I . 'Player 2 mulligans'],
    [['t' => 'KEEP_HAND', 'p' => 1], $I . 'Player 1 keeps their hand'],
    [['t' => 'GAME_END', 'winner' => 2, 'reason' => 'Base Destroyed'], $I . '*** Player 2 wins — Base Destroyed ***'],
    [['t' => 'GAME_END', 'winner' => 'Draw', 'reason' => 'Time'], $I . '*** Game ends in a draw — Time ***'],
    [['t' => 'UNDO', 'at' => 'R2.A.3', 'by' => 1], '    ·· Player 1 undid back to R2.A.3 ··'],
    [['t' => 'UNDO', 'at' => 'R2.A.3'], '    ·· A player undid back to R2.A.3 ··'],
    [['t' => 'PHASE_START', 'phase' => 'action'], ' ── action ──'],
];
foreach ($cases as [$e, $want]) {
    $got = L($e, $cards);
    SwuPgnTestCheck($got === $want, "§16 {$e['t']}: " . $want, 'got ' . var_export($got, true));
}

// ── these print nothing ─────────────────────────────────────────────────────────────────────────
foreach (['MOVE', 'EXHAUST', 'READY', 'EXHAUST_RESOURCES', 'READY_RESOURCES', 'STATS', 'CHOICE', 'MODAL_CHOICE', 'SHUFFLE', 'PHASE_END', 'ROUND_END', 'ABILITY_RESET', 'SOMETHING_NEW'] as $t) {
    $got = L(['t' => $t, 'p' => 1, 'card' => 'SOR#108', 'from' => 'hand', 'to' => 'ground', 'amount' => 1], $cards);
    SwuPgnTestCheck($got === '', "§16: $t prints nothing", var_export($got, true));
}

// ── nm(): CARDS index, copies, fallback ─────────────────────────────────────────────────────────
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'SOR#999:3'], $cards), $I . 'Player 1 resources SOR#999 #3', '§6.5/§16: an id the index does not cover falls back to the id itself (copy appended)');
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'SOR#108'], $cards), $I . 'Player 1 resources Wampa', '§16: names come from the file\'s own %%% CARDS');
SwuPgnTestEq(R([['t' => 'RESOURCE', 'p' => 1, 'card' => 'SOR#108:2']], $cards, ['SOR#108' => 'Big Snow Beast']), $I . 'Player 1 resources Big Snow Beast #2', '§16: a supplied card index is used instead of the file\'s');
SwuPgnTestEq(SwuPgnName([], 'TOKEN:advantage#5844562972:2'), 'TOKEN:advantage#5844562972 #2', '§6.1: strip :N first, also on a token');
SwuPgnTestEq([SwuPgnWho(1), SwuPgnWho(2), SwuPgnWho(3), SwuPgnWho('1')], ['Player 1', 'Player 2', '', ''], '§16 who()');
SwuPgnTestEq(L(['t' => 'PASS'], $cards), '  1.  passes', '§16 who() of a missing seat is "" (rendered as-is)');

// ── numbering, resets, indentation ──────────────────────────────────────────────────────────────
$ev = [['t' => 'PHASE_START', 'phase' => 'action']];
for ($i = 0; $i < 10; $i++) { $ev[] = ['t' => 'PASS', 'p' => 1 + $i % 2]; $ev[] = ['t' => 'DAMAGE', 'src' => 'x', 'tgt' => 'base@1', 'amt' => 1, 'damageType' => 'ability', 'hp' => 29 - $i]; }
$ev[] = ['t' => 'PHASE_START', 'phase' => 'regroup'];
$ev[] = ['t' => 'CLAIM_INITIATIVE', 'p' => 2];
$ev[] = ['t' => 'ROUND_START', 'round' => 2];
$ev[] = ['t' => 'PASS', 'p' => 1];
$lines = explode("\n", R($ev, $cards));
SwuPgnTestEq([$lines[19], $lines[20], $lines[21]], [' 10. Player 2 passes', $I . '1 damage to Player 1\'s base — 20 HP left', ' ── regroup ──'], '§16: the number is right-aligned in 2 columns; consequences indent under it');
SwuPgnTestEq($lines[22], '  1. Player 2 claims initiative', '§16: the counter resets at PHASE_START');
SwuPgnTestEq(array_slice($lines, 23), ['', str_repeat('═', 78), ' ROUND 2', str_repeat('═', 78), '', '  1. Player 1 passes'], '§16: ROUND_START with no keyframe: blank, rule, banner only, rule, blank; counter reset');
$lines = explode("\n", R([['t' => 'UNDO', 'at' => 'R1.A.1-undo'], ['t' => 'PASS', 'p' => 1], ['t' => 'UNDO', 'at' => 'R1.A.2', 'by' => 2], ['t' => 'PASS', 'p' => 2]], $cards));
SwuPgnTestEq($lines, ['    ·· A player undid back to R1.A.1-undo ··', '  1. Player 1 passes', '    ·· Player 2 undid back to R1.A.2 ··', '  2. Player 2 passes'], '§16: the UNDO marker is neither numbered nor indented and does not touch the counter');

// ── round banner + board summary ────────────────────────────────────────────────────────────────
$kf = SwuPgnTestKeyframe(
    ['baseHp' => 16, 'baseMaxHp' => 33, 'handSize' => 3, 'resourcesReady' => 2, 'resourcesExhausted' => 5, 'deckSize' => 31,
        'leader' => ['id' => 'SOR#010', 'deployed' => true, 'exhausted' => true, 'epicActionUsed' => true],
        'cards' => [
            SwuPgnTestCard('SOR#108', 'ground', ['power' => 4, 'hp' => 5]),
            SwuPgnTestCard('JTL#100', 'space', ['power' => 2, 'hp' => 3, 'damage' => 1, 'exhausted' => true, 'shields' => 2, 'experience' => 1, 'statusTokens' => ['advantage' => 1, 'weakness' => 2], 'upgrades' => ['LOF#215', 'SOR#010'], 'captured' => ['SOR#045:2']]),
            SwuPgnTestCard('SOR#108:2', 'space', ['power' => 4]),
        ]],
    ['baseHp' => 22, 'baseMaxHp' => 28, 'handSize' => 2, 'resourcesReady' => 5, 'leader' => ['id' => 'SOR#005', 'deployed' => false, 'exhausted' => true, 'epicActionUsed' => false]],
    ['round' => 7, 'initiative' => 2]);
unset($kf['players']['2']['deckSize']);
$lines = explode("\n", R([['t' => 'ROUND_START', 'round' => 7, 'keyframe' => $kf]], $cards));
SwuPgnTestEq($lines, [
    '',
    str_repeat('═', 78),
    ' ROUND 7                                                 initiative: Player 2 ',
    ' P1  base 16/33   hand 3   resources 2/7   deck 31   leader deployed',
    '      ground: Wampa 4/5',
    '      space: X-Wing Vehicle 2/3 [1 dmg, exhausted, 2 shield, 1 xp, 1 advantage, 2 weakness, Ascension Cable, Luke Skywalker, Faithful Friend, holds Cell Block Guard #2]  ·  Wampa #2',
    ' P2  base 22/28   hand 2   resources 5/5   leader exhausted',
    str_repeat('═', 78),
    '',
], '§16 board summary: banner 78 wide (Appendix A form), deck/leader parts, arenas, card state in the spec\'s order, stats only when both present');
SwuPgnTestEq(strlen(str_replace('═', '=', $lines[1])), 78, 'the rule is 78 wide');
$kf2 = SwuPgnTestKeyframe(['leader' => ['id' => 'SOR#010', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false]], [], ['round' => 1, 'initiative' => 1]);
$lines = explode("\n", R([['t' => 'ROUND_START', 'round' => 1, 'keyframe' => $kf2]], $cards));
SwuPgnTestEq([$lines[2], $lines[3], $lines[4]], [' ROUND 1                                                 initiative: Player 1 ', ' P1  base 30/30   hand 0   resources 0/0   leader ready', ' P2  base 30/30   hand 0   resources 0/0'], '§16: "leader ready"; a seat without leader/deckSize prints neither');
$lines = explode("\n", R([['t' => 'ROUND_START', 'round' => 3, 'keyframe' => ['players' => ['1' => SwuPgnTestSeatKeyframe(1)]]]], $cards));
SwuPgnTestEq($lines, ['', str_repeat('═', 78), ' ROUND 3', str_repeat('═', 78), ''], '§16: a damaged keyframe → no summary, just the banner');
$lines = explode("\n", R([['t' => 'ROUND_START', 'round' => 3, 'keyframe' => SwuPgnTestKeyframe([], [], ['initiative' => null])]], $cards));
SwuPgnTestEq($lines[2], ' ROUND 3', 'a keyframe with no initiative holder prints no initiative');
SwuPgnTestEq(R([['t' => 'ROUND_END', 'round' => 3, 'keyframe' => SwuPgnTestKeyframe()]], $cards), '', '§16: ROUND_END prints nothing, keyframe or not');

// ── 2026-10-28 text: the new records' wording ───────────────────────────────────────────────────
$cards[] = ['id' => 'SOR#154', 'name' => 'Rallying Cry'];
$cards[] = ['id' => 'JTL#002', 'name' => 'Admiral Ackbar, Brilliant Strategist'];
$new = [
    [['t' => 'LOOK', 'p' => 1, 'owner' => 2, 'zone' => 'hand', 'cards' => ['SOR#045'], 'count' => 1], $I . "Player 1 looks at 1 card of Player 2's hand: Cell Block Guard"],
    [['t' => 'LOOK', 'p' => 1, 'owner' => 1, 'zone' => 'deck', 'cards' => ['SOR#108', 'SOR#150'], 'count' => 2], $I . "Player 1 looks at 2 cards of Player 1's deck: Wampa · Vanquish"],
    [['t' => 'LOOK', 'p' => 2, 'owner' => 2, 'zone' => 'deck', 'cards' => [], 'count' => 3], $I . "Player 2 looks at 3 cards of Player 2's deck"],
    [['t' => 'NAME', 'p' => 1, 'name' => 'Battlefield Marine'], $I . 'Player 1 names Battlefield Marine'],
    [['t' => 'SEARCH', 'p' => 1, 'owner' => 1, 'seen' => ['SOR#108', 'SOR#150']], $I . 'Player 1 searches their deck'],
    [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#108', 'kind' => 'triggered', 'usedBy' => 'JTL#002'], $I . 'Wampa uses an ability (used by Admiral Ackbar, Brilliant Strategist)'],
    [['t' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#108', 'kind' => 'triggered', 'grantedBy' => 'LOF#215', 'usedBy' => 'JTL#002', 'ability' => 'a', 'limit' => ['max' => 1, 'per' => 'round']],
        $I . 'Wampa uses an ability (granted by Ascension Cable) (used by Admiral Ackbar, Brilliant Strategist)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@3', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108', 'SOR#108:2'], 'duration' => 'phase', 'text' => 'Each friendly unit gains Raid 2 this phase'],
        $I . 'Player 1: Rallying Cry — Each friendly unit gains Raid 2 this phase on Wampa · Wampa #2 (for this phase)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 2, 'seat' => 2, 'duration' => 'round'], $I . 'Player 2: Rallying Cry for Player 2 (for this round)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'base@1', 'p' => 1, 'seat' => 'both', 'duration' => 'game'], $I . "Player 1: Player 1's base for both players (for the rest of the game)"],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108'], 'duration' => 'attack'], $I . 'Player 1: Rallying Cry on Wampa (for this attack)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#108:2', 'p' => 1, 'targets' => ['SOR#045'], 'duration' => 'whileSourceInPlay'], $I . 'Player 1: Wampa #2 on Cell Block Guard (while Wampa #2 is in play)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108'], 'duration' => 'until', 'untilText' => 'the phase ends or an action is taken'], $I . 'Player 1: Rallying Cry on Wampa (until the phase ends or an action is taken)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108'], 'duration' => 'until'], $I . 'Player 1: Rallying Cry on Wampa (until a condition is met)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108'], 'duration' => 'delayed'], $I . 'Player 1: Rallying Cry on Wampa (delayed)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108'], 'duration' => 'nextTurn'], $I . 'Player 1: Rallying Cry on Wampa (nextTurn)'],
    [['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108'], 'duration' => 'phase', 'text' => 'Gains {keyword:restore:2}, {keyword:sentinel} and costs {resource:1} less, {resource:3} more; :villainy::aggression: or :heroism: :cunning:'],
        $I . 'Player 1: Rallying Cry — Gains Restore 2, Sentinel and costs 1 resource less, 3 resources more; Villainy Aggression or Heroism Cunning on Wampa (for this phase)'],
    [['t' => 'EFFECT_END', 'id' => 'fx@9', 'reason' => 'expired'], $I . 'Effect fx@9 ends (expired)'],
    [['t' => 'EFFECT_END', 'id' => 'fx@9', 'reason' => 'fired'], $I . 'Effect fx@9 ends (it resolved)'],
    [['t' => 'EFFECT_END', 'id' => 'fx@9', 'reason' => 'sourceLeft'], $I . 'Effect fx@9 ends (its source left play)'],
    [['t' => 'EFFECT_END', 'id' => 'fx@9', 'reason' => 'targetLeft', 'targets' => ['SOR#108:2']], $I . 'Effect fx@9 ends on Wampa #2 (its target changed zone)'],
    [['t' => 'EFFECT_END', 'id' => 'fx@9', 'reason' => 'cancelled'], $I . 'Effect fx@9 ends (cancelled)'],
    [['t' => 'EFFECT_END', 'id' => 'fx@9', 'reason' => 'superseded'], $I . 'Effect fx@9 ends (superseded)'],
    [['t' => 'PASS', 'p' => 1, 'simul' => 'R1.A.1'], '  1. Player 1 passes'],
    [['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'SOR#045', 'amt' => 4, 'damageType' => 'combat', 'hp' => 0, 'simul' => 'R1.A.1'], $I . '4 damage to Cell Block Guard — 0 HP left'],
];
foreach ($new as [$e, $want]) {
    $got = L($e, $cards);
    SwuPgnTestCheck($got === $want, "§16 {$e['t']}: " . $want, 'got ' . var_export($got, true));
}
// EFFECT_END names the src of the EFFECT_START with the same id earlier in the file.
$lines = explode("\n", R([
    ['t' => 'EFFECT_START', 'id' => 'fx@3', 'src' => 'SOR#154', 'p' => 1, 'targets' => ['SOR#108', 'SOR#108:2'], 'duration' => 'phase'],
    ['t' => 'EFFECT_START', 'id' => 'fx@4', 'src' => 'SOR#108:2', 'p' => 1, 'seat' => 1, 'duration' => 'phase'],
    ['t' => 'EFFECT_END', 'id' => 'fx@3', 'reason' => 'targetLeft', 'targets' => ['SOR#108']],
    ['t' => 'EFFECT_END', 'id' => 'fx@3', 'reason' => 'expired'],
    ['t' => 'EFFECT_END', 'id' => 'fx@4', 'reason' => 'expired'],
], $cards));
SwuPgnTestEq(array_slice($lines, 2), [$I . 'Effect from Rallying Cry ends on Wampa (its target changed zone)', $I . 'Effect from Rallying Cry ends (expired)', $I . 'Effect from Wampa #2 ends (expired)'],
    '§16 EFFECT_END: "Effect from {nm(src)} ends", src from the EFFECT_START with the same id');

// ── numbering: a lettered seq is never numbered, whatever its type ──────────────────────────────
$lines = explode("\n", R([
    ['seq' => 'R1.A.1', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'JTL#002', 'kind' => 'action'],
    ['seq' => 'R1.A.1a', 't' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => 'ground', 'cost' => 2],
    ['seq' => 'R1.A.1b', 't' => 'ATTACK', 'p' => 1, 'atk' => 'SOR#108', 'def' => 'base@2', 'defenderType' => 'base'],
    ['seq' => 'R1.A.1c', 't' => 'ABILITY_ACTIVATE', 'p' => 1, 'card' => 'SOR#108', 'kind' => 'action'],
    ['seq' => 'R1.A.2', 't' => 'PASS', 'p' => 2],
], $cards));
SwuPgnTestEq($lines, ['  1. Player 1 uses Admiral Ackbar, Brilliant Strategist', $I . 'Player 1 plays Wampa to ground (cost 2)',
    $I . "Player 1 attacks Player 2's base with Wampa", $I . 'Player 1 uses Wampa', '  2. Player 2 passes'],
    '§16: a play / attack / action ability made while an ability resolves (lettered seq) is indented, not numbered');
$ev = [['t' => 'PHASE_START', 'phase' => 'action']];
for ($i = 0; $i < 100; $i++) $ev[] = ['t' => 'PASS', 'p' => 1 + $i % 2];
$lines = explode("\n", R($ev, $cards));
SwuPgnTestEq([$lines[99], $lines[100]], [' 99. Player 1 passes', ' 100. Player 2 passes'], '§16: action numbers from 100 up take their width');

// ── nm(): cut like a card name — controls/bidi → space, > 120 UTF-16 units → 119 + "…" ─────────
$long = str_repeat('a', 130);
$cut = ['id' => 'ZZZ#001', 'name' => $long];
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#001'], [$cut]), $I . 'Player 1 resources ' . str_repeat('a', 119) . '…', '§16 nm(): a name over 120 UTF-16 units is cut to its first 119 and "…"');
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#001'], [['id' => 'ZZZ#001', 'name' => str_repeat('b', 120)]]), $I . 'Player 1 resources ' . str_repeat('b', 120), '§16 nm(): exactly 120 is not cut');
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#001:2'], [['id' => 'ZZZ#001', 'name' => str_repeat('c', 118)]]), $I . 'Player 1 resources ' . str_repeat('c', 118) . ' …', '§16 nm(): the " #N" is cut with the name (118 + " #2" = 121 → 119 + "…")');
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#002'], [['id' => 'ZZZ#002', 'name' => str_repeat('😀', 60)]]), $I . 'Player 1 resources ' . str_repeat('😀', 60), '§16 nm(): length counts UTF-16 units — 60 astral characters are 120, not cut');
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#002'], [['id' => 'ZZZ#002', 'name' => str_repeat('😀', 61)]]), $I . 'Player 1 resources ' . str_repeat('😀', 59) . "\u{FFFD}…", '§16 nm(): a cut through a surrogate pair keeps the lone half as U+FFFD (what UTF-8 makes of it)');
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#003'], [['id' => 'ZZZ#003', 'name' => str_repeat('é', 125)]]), $I . 'Player 1 resources ' . str_repeat('é', 119) . '…', '§16 nm(): a BMP non-ASCII name counts one unit per character, not per byte');
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#' . str_repeat('9', 130)], $cards), $I . 'Player 1 resources ZZZ#' . str_repeat('9', 115) . '…', '§16 nm(): an id the index does not cover is cut the same way');
$ctl = "A\nB\rC\tD\x00E\u{0085}F\u{2028}G\u{2029}H\u{202E}I\u{2066}J\u{200F}K\u{061C}L\x1FM\x7FN";
SwuPgnTestEq(L(['t' => 'RESOURCE', 'p' => 1, 'card' => 'ZZZ#004'], [['id' => 'ZZZ#004', 'name' => $ctl]]), $I . "Player 1 resources A B C D E F G H I J K L M\x7FN",
    '§16 nm(): C0, C1, U+2028/9 and bidi controls become spaces (DEL is neither C0 nor C1: kept)');
SwuPgnTestEq(L(['t' => 'NAME', 'p' => 2, 'name' => "Wam\u{202E}pa" . str_repeat('x', 130)], $cards), $I . 'Player 2 names Wam pa' . str_repeat('x', 113) . '…', '§16 NAME: the name is cut as nm cuts a card name');
SwuPgnTestEq(L(['t' => 'EFFECT_START', 'id' => 'fx@1', 'src' => 'SOR#154', 'p' => 1, 'seat' => 1, 'duration' => 'until', 'untilText' => "it\nends" . str_repeat('u', 130), 'text' => str_repeat('t', 125)], $cards),
    $I . 'Player 1: Rallying Cry — ' . str_repeat('t', 119) . '… for Player 1 (until it ends' . str_repeat('u', 112) . '…)', '§16 EFFECT_START: plain(text) and untilText are cut as nm cuts a card name');
// Every other printed field: controls become spaces, but nothing else is cut.
SwuPgnTestEq(L(['t' => 'PLAY', 'p' => 1, 'card' => 'SOR#108', 'zone' => "gro\nund" . str_repeat('z', 130), 'cost' => 2], $cards), '  1. Player 1 plays Wampa to gro und' . str_repeat('z', 130) . ' (cost 2)', '§16 layout: a control in any printed field is a space; only nm-cut text is cut');
SwuPgnTestEq(L(['t' => 'GAME_END', 'winner' => 1, 'reason' => "Base\u{2028}Destroyed\u{202E}"], $cards), $I . '*** Player 1 wins — Base Destroyed  ***', '§16 layout: GAME_END reason — one record is never more than one line');
SwuPgnTestEq(L(['t' => 'UNDO', 'at' => "R1.A.1\nX"], $cards), '    ·· A player undid back to R1.A.1 X ··', '§16 layout: UNDO at');
SwuPgnTestEq(L(['t' => 'STATUS_TOKEN', 'card' => 'SOR#108', 'token' => "bad\ntoken", 'count' => 1], $cards), $I . 'Wampa gains 1 bad token', '§16 layout: STATUS_TOKEN token');
SwuPgnTestEq(L(['t' => 'PHASE_START', 'phase' => "act\u{0085}ion"], $cards), ' ── act ion ──', '§16 layout: PHASE_START phase');

// ── copies: a copy unit is named by its STATS name (cut), tracked by full id ────────────────────
$copyStats = ['t' => 'STATS', 'card' => 'SOR#045:2', 'power' => 3, 'hp' => 3, 'keywords' => [], 'traits' => ['clone'], 'copyOf' => 'SOR#108', 'name' => "Battlefield\nMarine", 'cost' => 2, 'aspects' => ['command']];
$hit = ['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'SOR#045:2', 'amt' => 1, 'damageType' => 'combat', 'hp' => 2];
$hitOther = ['t' => 'DAMAGE', 'src' => 'SOR#108', 'tgt' => 'SOR#045:3', 'amt' => 1, 'damageType' => 'combat', 'hp' => 2];
$lines = explode("\n", R([
    $hit, $copyStats, $hit, $hitOther,
    ['t' => 'STATS', 'card' => 'SOR#045:2', 'power' => 3, 'hp' => 3, 'copyOf' => 'SOR#108'],   // copyOf without name: not a named copy
    $hit, $copyStats, $hit,
    ['t' => 'MOVE', 'card' => 'SOR#045:2', 'from' => 'ground', 'to' => 'space', 'p' => 1, 'kind' => 'unit'],
    $hit,
    ['t' => 'MOVE', 'card' => 'SOR#045:2', 'from' => 'space', 'to' => 'discard', 'p' => 1, 'kind' => 'unit'],
    $hit,
], $cards));
SwuPgnTestEq($lines, [
    $I . '1 damage to Cell Block Guard #2 — 2 HP left',
    $I . '1 damage to Battlefield Marine #2 — 2 HP left',
    $I . '1 damage to Cell Block Guard #3 — 2 HP left',
    $I . '1 damage to Cell Block Guard #2 — 2 HP left',
    $I . '1 damage to Battlefield Marine #2 — 2 HP left',
    $I . '1 damage to Battlefield Marine #2 — 2 HP left',
    $I . '1 damage to Cell Block Guard #2 — 2 HP left',
], '§16 nm(): a copy reads by its copied name (cut, " #N" kept) from its STATS until a STATS without copyOf+name or its MOVE out of an arena; per full id');
SwuPgnTestEq(L(['t' => 'DAMAGE', 'tgt' => 'SOR#045:2', 'amt' => 1, 'hp' => 2], $cards), $I . '1 damage to Cell Block Guard #2 — 2 HP left', 'copy names do not leak between renders');
$copyName = str_repeat('m', 118);
$lines = explode("\n", R([array_merge($copyStats, ['name' => $copyName]), $hit], $cards));
SwuPgnTestEq($lines[0], $I . '1 damage to ' . $copyName . ' …' . ' — 2 HP left', '§16 nm(): a copy name and its " #N" are cut together');
// A ROUND_START keyframe re-syncs copy names from its cards (ROUND_END does not) and the board summary shows them.
$kfCopy = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#045:2', 'ground', ['power' => 3, 'hp' => 3, 'copyOf' => 'SOR#108', 'name' => 'Battlefield Marine']),
    SwuPgnTestCard('SOR#045:3', 'ground', ['copyOf' => 'SOR#108'])]]);
$kfPlain = SwuPgnTestKeyframe(['cards' => [SwuPgnTestCard('SOR#045:2', 'ground', ['power' => 3, 'hp' => 3])]]);
$lines = explode("\n", R([
    ['t' => 'ROUND_START', 'round' => 1, 'active' => 1, 'keyframe' => $kfCopy], $hit, $hitOther,
    ['t' => 'ROUND_END', 'round' => 1, 'keyframe' => $kfPlain], $hit,
    ['t' => 'ROUND_START', 'round' => 2, 'active' => 1, 'keyframe' => $kfPlain], $hit,
], $cards));
SwuPgnTestEq($lines[4], '      ground: Battlefield Marine #2 3/3  ·  Cell Block Guard #3', '§16 board summary: a copy card shows its name (when it has copyOf AND name), then " #N"');
SwuPgnTestEq([$lines[8], $lines[9], $lines[10], $lines[19]], [$I . '1 damage to Battlefield Marine #2 — 2 HP left', $I . '1 damage to Cell Block Guard #3 — 2 HP left',
    $I . '1 damage to Battlefield Marine #2 — 2 HP left', $I . '1 damage to Cell Block Guard #2 — 2 HP left'],
    '§16 nm(): a ROUND_START keyframe re-syncs copy names; a ROUND_END keyframe does not');

// ── faces: tracked per copy by full id; only a ROUND_START keyframe re-syncs ────────────────────
$tk = [['id' => 'HMW#004', 'name' => 'Grand Moff Tarkin, Tyrant of the Outer Rim', 'backSideName' => 'The Death Star, Icon of Tyranny']];
$leaderKf = fn(bool $deployed) => SwuPgnTestKeyframe(['leader' => ['id' => 'HMW#004', 'deployed' => $deployed, 'exhausted' => false, 'epicActionUsed' => $deployed]],
    ['leader' => ['id' => 'HMW#004:2', 'deployed' => false, 'exhausted' => false, 'epicActionUsed' => false]]);
$lines = explode("\n", R([
    ['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'HMW#004', 'zone' => 'space'],
    ['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'ability', 'defeatedBy' => 'HMW#004'],
    ['t' => 'DEFEAT', 'card' => 'base@1', 'reason' => 'ability', 'defeatedBy' => 'HMW#004:2'],
    ['t' => 'ROUND_END', 'round' => 1, 'keyframe' => $leaderKf(false)],
    ['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'ability', 'defeatedBy' => 'HMW#004'],
    ['t' => 'ROUND_START', 'round' => 2, 'active' => 1, 'keyframe' => $leaderKf(false)],
    ['t' => 'DEFEAT', 'card' => 'base@2', 'reason' => 'ability', 'defeatedBy' => 'HMW#004'],
], $tk));
SwuPgnTestEq([$lines[1], $lines[2], $lines[3], end($lines)], [
    $I . "Player 2's base is defeated by The Death Star, Icon of Tyranny",
    $I . "Player 1's base is defeated by Grand Moff Tarkin, Tyrant of the Outer Rim #2",
    $I . "Player 2's base is defeated by The Death Star, Icon of Tyranny",
    $I . "Player 2's base is defeated by Grand Moff Tarkin, Tyrant of the Outer Rim",
], '§16 nm(): the side that is up is tracked per copy (full id); a ROUND_END keyframe does not re-sync it, a ROUND_START one does');
SwuPgnTestEq(L(['t' => 'DEPLOY_LEADER', 'p' => 1, 'card' => 'HMW#004'], [['id' => 'HMW#004', 'name' => "Tar\nkin", 'backSideName' => str_repeat('d', 125)]]),
    '  1. Player 1 deploys Tar kin as ' . str_repeat('d', 119) . '…', '§16 front()/back() are cut the same way');

// ── board summary: the base line, the banner join rule, a missing seat ──────────────────────────
$kfBase = SwuPgnTestKeyframe(['baseUpgrades' => ['LOF#215', 'LOF#215:2'], 'baseCaptured' => ['SOR#045:4']], ['baseCaptured' => ['SOR#108:3']]);
$lines = explode("\n", R([['t' => 'ROUND_START', 'round' => 4, 'active' => 1, 'keyframe' => $kfBase]], $cards));
SwuPgnTestEq([$lines[3], $lines[4], $lines[5], $lines[6]], [' P1  base 30/30   hand 0   resources 0/0', '      base: Ascension Cable  ·  Ascension Cable #2  ·  holds Cell Block Guard #4',
    ' P2  base 30/30   hand 0   resources 0/0', '      base: holds Wampa #3'], '§16 board summary: the base: line names base upgrades, then "holds" each captive, joined by "  ·  "');
$wide = str_repeat('9', 57);
$lines = explode("\n", R([['t' => 'ROUND_START', 'round' => $wide, 'active' => 1, 'keyframe' => SwuPgnTestKeyframe()]], $cards));
SwuPgnTestEq($lines[2], " ROUND {$wide}initiative: Player 1 ", '§16 banner: two parts that do not fit in 78 columns are joined with no space');
$fit = str_repeat('9', 40);
$lines = explode("\n", R([['t' => 'ROUND_START', 'round' => $fit, 'active' => 1, 'keyframe' => SwuPgnTestKeyframe()]], $cards));
SwuPgnTestEq([$lines[2], mb_strlen($lines[2])], [" ROUND {$fit}" . str_repeat(' ', 78 - 7 - 40 - 21) . 'initiative: Player 1 ', 78], '§16 banner: padded so "initiative: Player N " ends at column 78');
$noP2 = SwuPgnTestKeyframe(); unset($noP2['players']['2']);
SwuPgnTestEq(explode("\n", R([['t' => 'ROUND_START', 'round' => 5, 'active' => 1, 'keyframe' => $noP2]], $cards)), ['', str_repeat('═', 78), ' ROUND 5', str_repeat('═', 78), ''], '§13/§16: a keyframe missing a seat is damaged → banner only');

// ── a story is at most 16,777,216 characters ────────────────────────────────────────────────────
$capNames = ['X#1' => str_repeat('a', 200)];
$ids = []; for ($i = 2; $i <= 1001; $i++) $ids[] = "X#1:$i";
$big = [];
for ($i = 0; $i < 140; $i++) $big[] = ['seq' => 'R1.A.' . ($i + 1), 't' => 'DRAW', 'p' => 1, 'count' => 1000, 'cards' => $ids];
$big[] = ['seq' => 'R1.A.900', 't' => 'MOVE', 'card' => 'X#1', 'from' => 'hand', 'to' => 'discard', 'p' => 1];
$story = SwuPgnRenderEvents($big, $capNames);
$lines = explode("\n", $story);
$u16 = fn(string $s) => strlen(mb_convert_encoding($s, 'UTF-16LE', 'UTF-8')) / 2;
$stop = '… the story stops here: it is longer than 16777216 characters';
$before = 0; foreach (array_slice($lines, 0, -2) as $l) $before += $u16($l) + 1;
$atStop = $before + $u16($lines[count($lines) - 2]) + 1;
SwuPgnTestCheck(end($lines) === $stop && count(array_keys($lines, $stop, true)) === 1, '§16 cap: an over-long story ends with the stop line, once', end($lines));
SwuPgnTestCheck($atStop > 16777216 && $before <= 16777216, '§16 cap: the stop line comes before the first record printed while the story is already over 16,777,216 (line feed counted per line)', "before $before, at stop $atStop");
SwuPgnTestCheck(count($lines) < 140, '§16 cap: nothing is printed after the stop line', count($lines));
$small = SwuPgnRenderEvents(array_slice($big, 0, 3), $capNames);
SwuPgnTestCheck(!str_contains($small, 'story stops'), '§16 cap: a story under the cap has no stop line');
$exact = SwuPgnRenderEvents(array_merge(array_slice($big, 0, 1), [['seq' => 'R1.A.2', 't' => 'MOVE', 'card' => 'X#1', 'from' => 'hand', 'to' => 'deck', 'p' => 1]]), $capNames);
SwuPgnTestCheck(substr_count($exact, "\n") === 0, 'a record that prints nothing adds no line');
unset($big, $story, $lines, $ids);
// Exactly at the edge: two lines of 16,777,215 units between them are 16,777,217 with their line feeds — over the cap.
$undo = fn(int $len) => ['t' => 'UNDO', 'at' => str_repeat('u', $len - 33)];   // '    ·· A player undid back to ' + at + ' ··'
$edge = [$undo(8000000), $undo(16777215 - 8000000), ['t' => 'PASS', 'p' => 1]];
$lines = explode("\n", SwuPgnRenderEvents($edge, []));
SwuPgnTestEq([count($lines), mb_strlen($lines[0]) + mb_strlen($lines[1]), end($lines)], [3, 16777215, $stop], '§16 cap: a line feed counts per line (16,777,215 + 2 > 16,777,216 → the next record stops)');
$edge[1] = $undo(16777215 - 8000000 - 1);
$lines = explode("\n", SwuPgnRenderEvents($edge, []));
SwuPgnTestEq(end($lines), '  1. Player 1 passes', '§16 cap: at exactly 16,777,216 the story is not yet over the cap');
unset($edge, $lines);

// ── story check helper ──────────────────────────────────────────────────────────────────────────
$evs = SwuPgnTestSeq([['t' => 'PHASE_START', 'phase' => 'action'], ['t' => 'PASS', 'p' => 1]]);
$doc = SwuPgnParse(SwuPgnTestFile($evs, ['story' => [' ── action ──', '  1. Player 1 passes', '', '']]));
SwuPgnTestEq(SwuPgnStoryMatches($doc), ['present' => true, 'matches' => true, 'line' => null], 'SwuPgnStoryMatches: trailing blank lines before the next banner are layout, not story');
$doc = SwuPgnParse(SwuPgnTestFile($evs, ['story' => [' ── action ──', '  1. Player 1 passed']]));
SwuPgnTestEq(SwuPgnStoryMatches($doc), ['present' => true, 'matches' => false, 'line' => 2], 'SwuPgnStoryMatches reports the first differing line');
$doc = SwuPgnParse(SwuPgnTestFile($evs));
SwuPgnTestEq(SwuPgnStoryMatches($doc), ['present' => false, 'matches' => false, 'line' => null], 'SwuPgnStoryMatches: no STORY section');
// A story that opens on a round banner starts with a blank line; §4 has the parser drop it, so the check must too.
$evs = SwuPgnTestSeq([['t' => 'ROUND_START', 'round' => 1, 'active' => 1], ['t' => 'PASS', 'p' => 1]]);
$doc = SwuPgnParse(SwuPgnTestFile($evs, ['story' => explode("\n", SwuPgnRenderEvents($evs, []))]));
SwuPgnTestEq(SwuPgnStoryMatches($doc), ['present' => true, 'matches' => true, 'line' => null], 'SwuPgnStoryMatches: leading blank lines before the story are layout, not story (§4)');

SwuPgnTestFinish();
