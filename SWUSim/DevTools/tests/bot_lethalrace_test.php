<?php
// Feature 'lethalrace' (p34) — rule 2 'lethal-now' must not take a lethal the opponent can race. SWU alternates actions: a
// lethal that needs K attacks hands the opponent K-1 actions in between, and if their ready attackers kill me in those,
// the "lethal" never lands. Found 2026-10-04 in a 400-game Hemlock Red vs Vader Yellow sweep (.claude/tmp/hemlock/hv4):
// seed hv024, R6 — Hemlock on 2 HP, 4 attacks to finish a 12-HP Freetown, nine Vader ships ready, Hyperspace Disaster
// castable: rule 2 swung at the base and Vader killed Hemlock on the next action. When the opponent could kill the firing
// seat in the gaps, the firing seat lost 80 of 84 games (Hemlock 37/40, Vader 43/44).
// Fixtures (dictionary-checked): HMW_003 Doctor Hemlock · HMW_027 Bioweapons Lab (30) · LOF_130 HK-47 2/4 · LAW_172 Storm
//   Raider 2/2 Raid 1 · ASH_048 Imperial Armored Commando 4/3 Sentinel · SEC_078 Hyperspace Disaster (7) · ASH_026 Freetown (30)
//   · JTL_006 Darth Vader · SEC_215 Emissary's Sheathipede 2/4 (space) · LAW_135 Pirate Snub Fighter 2/3 (space) · SOR_095
//   Battlefield Marine 3/3 (ground)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lethalrace_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/Custom/BotEvaluator.php';
include_once './SWUSim/Rl/CardTags.php';
include_once './SWUSim/Custom/BotStyles.php';
include_once './SWUSim/Custom/BotResourcing.php';
include_once './SWUSim/Custom/BotGuides.php';
include_once './SWUSim/Custom/BotFallback.php';
include_once './SWUSim/Custom/BotRules.php';
include_once './SWUSim/BotHeuristic.php';
$check(SWUBotVariantDisabled('no-lethalrace') === ['lethalrace'], 'lethalrace is switchable; got ' . json_encode(SWUBotVariantDisabled('no-lethalrace')));
$check(in_array('lethalrace', SWUBotFeatureGroups()['p34'] ?? [], true), 'lethalrace is in group p34');

// Rule 2 under a variant: the variant's disabled list is what SWUBotFeatureOn reads (SWUBotHeuristicChoose sets it the same way).
$rule2 = function (string $variant = '') use ($botCtx) {
    $saved = $GLOBALS['SWUBotDisabledFeatures'] ?? [];
    $GLOBALS['SWUBotDisabledFeatures'] = SWUBotVariantDisabled($variant);
    $pick = SWUBotRuleLethalNow($botCtx('softcontrol'));
    $GLOBALS['SWUBotDisabledFeatures'] = $saved;
    return $pick === null ? null : strval($pick['cardID']);
};
$stack = function (string $variant = '') use (&$gameName) {
    SWUBotResetCoverage(); $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant);
    return [$p === null ? null : strval($p['cardID']), array_keys($GLOBALS['SWUBotCoverage'][1] ?? [])];
};
// Seat 1 = Hemlock (leader exhausted: no Action on offer), three ready ground attackers 2 + 3 (Raid) + 4 = 9 into a 9-HP
// Freetown — lethal in THREE attacks. $myDamage sets my base; $theirs lists seat 2's units as [arena, cardID, ready].
$board = function (int $myDamage, array $theirs, int $theirDamage = 21, array $mine = ['LOF_130', 'LAW_172', 'ASH_048']) {
    return function ($b) use ($myDamage, $theirs, $theirDamage, $mine) {
        $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027', $myDamage);
        $b->TheirLeader('JTL_006', false); $b->TheirBase('ASH_026', $theirDamage);
        $b->FillResourcesForPlayer(1, 'LAW_097', 7);
        $b->WithCardInHandForPlayer(1, 'SEC_078');
        foreach ($mine as $cid) $b->WithGroundUnitForPlayer(1, $cid, true);
        foreach ($theirs as [$arena, $cid, $ready]) {
            if ($arena === 'space') $b->WithSpaceUnitForPlayer(2, $cid, $ready); else $b->WithGroundUnitForPlayer(2, $cid, $ready);
        }
    };
};
$ships = [['space', 'SEC_215', true], ['space', 'LAW_135', true]];   // 2 + 2 ready, no Sentinel of mine in space

// A) THE SWEEP BOARD (hv024 in miniature): 2 HP left, 3 attacks needed, their two ships deal 4 in the two gaps.
$build($board(28, $ships));
$check(SWUBotLethalNow(1, 2), 'A fixture: the summed potential is lethal (9 into 9)');
$check($rule2('no-lethalrace') !== null, 'A fixture: today rule 2 swings at the base; got ' . var_export($rule2('no-lethalrace'), true));
$check($rule2() === null, 'A: they kill me in the gaps — rule 2 stands aside; got ' . var_export($rule2(), true));
[$pick, $cov] = $stack();
$check($pick === 'myHand-0!FSM!', 'A: the stack casts Hyperspace Disaster instead; got ' . var_export($pick, true) . ' via ' . json_encode($cov));

// B) Their ships are EXHAUSTED: no attacks in the gaps — the lethal is safe, rule 2 takes it.
$build($board(28, [['space', 'SEC_215', false], ['space', 'LAW_135', false]]));
$check($rule2() !== null, 'B: their units are exhausted — rule 2 still takes the lethal');

// C) Their attackers are ground units behind my Sentinel (Imperial Armored Commando): none reach my base — safe.
$build($board(28, [['ground', 'SOR_095', true], ['ground', 'SOR_095', true]]));
$check($rule2() !== null, 'C: my Sentinel stops their ground attackers — rule 2 still takes the lethal');

// D) ONE attack is lethal (4 into 4): the opponent gets no action before it lands, however big their board.
$build($board(28, $ships, 26, ['ASH_048']));
$check($rule2() !== null, 'D: a one-attack lethal gives them no gap — rule 2 takes it');

// E) The boundary: 5 HP left against their 4 in the gaps — they fall one short, the lethal is safe.
$build($board(25, $ships));
$check($rule2() !== null, 'E: their 4 in the gaps is one short of my 5 — rule 2 takes the lethal');
// …and 4 HP left: their 4 is exactly enough — unsafe.
$build($board(26, $ships));
$check($rule2() === null, 'E: their 4 in the gaps kills me on exactly 4 — rule 2 stands aside');

bot_test_finish();
