<?php
// A lookahead PLAN must not commit an answer it had no reason for. Game 1647080 (2026-10-06, Hemlock softcontrol bot):
// facing lethal next round, rule 'break-lethal' chose to attack with deployed Doctor Hemlock (HMW_003, "On Attack: You may
// give a Weakness token to a unit"), and 'planned-answer' then gave the Weakness to the bot's OWN Anakin Skywalker.
// The line's score reads only the two clocks, which a -1/-1 does not move, so every answer TIED and the strict '>' in
// _SWUBotLookaheadContinue kept the FIRST one the bridge lists — a friendly unit (my zones come first). The fallback
// scorer had it right all along (own units < 0 < enemies). Fix: a plan step records every answer that tied for best, and
// SWUBotRulePlannedAnswer lets the fallback choose among them.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_plantie_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/BotHeuristic.php';
include_once './SWUSim/Custom/BotLookahead.php';

// The bot (seat 1): deployed Hemlock (ready), an exhausted Anakin and Snub Fighter. Opponent: deployed Grievous, Palpatine and
// three Spies, two of them Sentinels — game 1647080's board at the decision. $dmg = the bot's base damage. At 16 (the live
// game's value) killing the Sentinel Spy breaks lethal and the Weakness moves no clock: every answer TIES. At 18 the Weakness
// is DECISIVE (it finishes a second Spy), so the plan's answer must still be followed — the control.
$board = function (int $dmg = 16) use ($build) {
    $build(function ($b) {
        $b->MyLeader('HMW_003', true, true, true, 'unit', 2);
        $b->MyBase('HMW_033');
        $b->FillResourcesForPlayer(1, 'SOR_095', 6);
        $b->WithGroundUnitForPlayer(1, 'LOF_070', false);
        $b->WithSpaceUnitForPlayer(1, 'ASH_194', false, 1);
        $b->TheirLeader('HMW_008', false, true, true, 'unit');
        $b->WithGroundUnitForPlayer(2, 'SEC_T01', false);
        $b->WithGroundUnitForPlayer(2, 'SEC_082', true);
        $b->WithGroundUnitForPlayer(2, 'SEC_T01', true, 0, 0, 'SENTINEL^SEC_082');
        $b->WithGroundUnitForPlayer(2, 'SEC_T01', true, 0, 0, 'SENTINEL^SEC_082');
    });
    $b = &GetBase(1); $b[0]->Damage = $dmg;
};
$step = function (string $style) use (&$gameName, $act) {
    $l = SWUBotLegalActions($gameName, 1);
    $GLOBALS['SWUBotLastRule'] = '';
    $p = SWUBotHeuristicChoose($style, (array)$l['actions'], $l, '');
    $out = ['legal' => $l, 'pick' => strval($p['cardID'] ?? ''), 'rule' => strval($GLOBALS['SWUBotLastRule'] ?? '')];
    $act(1, intval($p['mode'] ?? 100), $out['pick']);
    return $out;
};

foreach (['softcontrol', 'midrange', 'hyperaggro'] as $style) {
    $board();
    global $playerID; $playerID = 1;
    $check(SWUBotClock(2, 1) === 1, "[$style] fixture: the opponent's clock is 1 (rule 4 is live)");
    $s0 = $step($style);
    $hem = SWUBotViewForMz(1, explode('!', $s0['pick'])[0]);
    $check($s0['rule'] === 'break-lethal' && ($hem['cardID'] ?? '') === 'HMW_003',
        "[$style] break-lethal plans the Hemlock attack (rule={$s0['rule']}, pick={$s0['pick']})");
    $s1 = $step($style);                                   // the attack target
    $check(($s1['legal']['decisionTooltip'] ?? '') === 'Choose_an_attack_target', "[$style] the attack target prompt");
    $s2 = $step($style);                                   // Hemlock's On Attack
    $check(($s2['legal']['decisionTooltip'] ?? '') === 'Give_a_Weakness_token_to_a_unit', "[$style] Hemlock's On Attack prompt is pending");
    $check(str_starts_with($s2['pick'], 'their'), "[$style] the Weakness goes to an ENEMY unit, not the bot's own; got {$s2['pick']} via {$s2['rule']}");
    $own = 0; foreach (array_merge(GetGroundArena(1), GetSpaceArena(1)) as $u) if (empty($u->removed)) $own += count(array_filter((array)($u->Subcards ?? []), fn($s) => ($s->CardID ?? '') === 'HMW_T02'));
    $check($own === 0, "[$style] no friendly unit carries a Weakness token ($own)");
}

// The lookahead's own record: every answer that tied for best is kept, so the plan can tell a decisive answer from a free one.
$board();
$legal = SWUBotLegalActions($gameName, 1);
$ctx = $botCtx('softcontrol');
SWUBotRuleBreakLethal($ctx);
$plan = $GLOBALS['SWUBotPlan'][1] ?? [];
$weak = null; foreach ($plan as $p) if (($p['tooltip'] ?? '') === 'Give_a_Weakness_token_to_a_unit') $weak = $p;
$check($weak !== null && count((array)($weak['tied'] ?? [])) > 1, 'the plan marks the Weakness step as a tie: ' . json_encode($weak['tied'] ?? null));

// The tie-break stays INSIDE the tie: at the real Weakness prompt, a plan step whose tied answers are two Spies must yield a
// Spy — never the fallback's favourite over the whole prompt (Grievous), which the lookahead did not rate as best.
$board();
$s0 = $step('softcontrol'); $s1 = $step('softcontrol');
$ctx = $botCtx('softcontrol');
$spies = []; $favourite = null;
foreach ($ctx['actions'] as $a) {
    $v = SWUBotViewForMz(1, strval($a['cardID']));
    if (str_starts_with(strval($a['cardID']), 'their') && ($v['cardID'] ?? '') === 'SEC_T01') $spies[] = strval($a['cardID']);
}
$favourite = strval(SWUBotFallbackChoose($ctx)['cardID'] ?? '');
$check(count($spies) >= 2 && !in_array($favourite, $spies, true), "fixture: two Spies, and the fallback's favourite is elsewhere ($favourite)");
$GLOBALS['SWUBotPlan'][1] = [['tooltip' => 'Give_a_Weakness_token_to_a_unit', 'answer' => $spies[0], 'tied' => array_slice($spies, 0, 2)]];
$got = strval(SWUBotRulePlannedAnswer($ctx)['cardID'] ?? '');
$check(in_array($got, array_slice($spies, 0, 2), true), "planned-answer breaks a tie among the TIED answers only: got $got");

// CONTROL — a DECISIVE plan step still binds. At 18 damage only a Weakness that finishes an enemy keeps lethal broken, so the
// best answers are a strict SUBSET of the prompt's (no friendly unit among them) and the bot must pick from that subset —
// the fallback only breaks the tie inside it.
$board(18);
$ctx = $botCtx('softcontrol');
$GLOBALS['SWUBotPlan'] = [];
SWUBotRuleBreakLethal($ctx);
$weak = null; foreach ($GLOBALS['SWUBotPlan'][1] ?? [] as $p) if (($p['tooltip'] ?? '') === 'Give_a_Weakness_token_to_a_unit') $weak = $p;
$best = (array)($weak['tied'] ?? []);
$check(!empty($best) && empty(array_filter($best, fn($m) => str_starts_with($m, 'my'))), 'control: the decisive answers are enemy units only: ' . json_encode($best));
$s0 = $step('softcontrol'); $s1 = $step('softcontrol'); $s2 = $step('softcontrol');
$offered = count((array)$s2['legal']['actions']);
$check(count($best) < $offered - 1, "control: the plan rules answers OUT (" . count($best) . " best of $offered offered)");
$check($s0['rule'] === 'break-lethal' && $s2['rule'] === 'planned-answer' && in_array($s2['pick'], $best, true),
    "control: the bot's pick is one of the decisive answers ({$s2['pick']} via {$s2['rule']})");

bot_test_finish();
