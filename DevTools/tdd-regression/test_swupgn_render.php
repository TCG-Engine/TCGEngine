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
    [['t' => 'DRAW', 'p' => 1, 'count' => 2, 'cards' => ['SOR#108', 'SOR#108:2']], $I . 'Player 1 draws 2: Wampa, Wampa #2'],
    [['t' => 'DRAW', 'p' => 2, 'count' => 2, 'cards' => []], $I . 'Player 2 draws 2'],
    [['t' => 'DISCARD', 'p' => 1, 'cards' => ['SOR#108', 'SOR#150']], $I . 'Player 1 discards Wampa, Vanquish'],
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
foreach (['MOVE', 'EXHAUST', 'READY', 'EXHAUST_RESOURCES', 'READY_RESOURCES', 'STATS', 'CHOICE', 'MODAL_CHOICE', 'SHUFFLE', 'PHASE_END', 'ROUND_END', 'SOMETHING_NEW'] as $t) {
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

// ── story check helper ──────────────────────────────────────────────────────────────────────────
$evs = SwuPgnTestSeq([['t' => 'PHASE_START', 'phase' => 'action'], ['t' => 'PASS', 'p' => 1]]);
$doc = SwuPgnParse(SwuPgnTestFile($evs, ['story' => [' ── action ──', '  1. Player 1 passes', '', '']]));
SwuPgnTestEq(SwuPgnStoryMatches($doc), ['present' => true, 'matches' => true, 'line' => null], 'SwuPgnStoryMatches: trailing blank lines before the next banner are layout, not story');
$doc = SwuPgnParse(SwuPgnTestFile($evs, ['story' => [' ── action ──', '  1. Player 1 passed']]));
SwuPgnTestEq(SwuPgnStoryMatches($doc), ['present' => true, 'matches' => false, 'line' => 2], 'SwuPgnStoryMatches reports the first differing line');
$doc = SwuPgnParse(SwuPgnTestFile($evs));
SwuPgnTestEq(SwuPgnStoryMatches($doc), ['present' => false, 'matches' => false, 'line' => null], 'SwuPgnStoryMatches: no STORY section');

SwuPgnTestFinish();
