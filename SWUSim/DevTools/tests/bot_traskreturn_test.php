<?php
// Feature 'traskreturn' (p39) — ASH_133 Trask Walker ("When Played/On Attack: Choose a unit in your discard pile that costs 7 or less.
// Either put that card on the bottom of your deck and heal 3 damage from your base or return it to your hand") takes back the best
// ANSWER and returns it to hand. Owner, Krennic (LAW) Blue questionnaire 2026-10-06: Trask Walker takes back "Chimaera".
// Traced (480 Krennic Blue games, baseline kb1): "Bottom + heal 3" 225 of 225 times. Two causes: the discard pick fell to the
// first-legal tiebreak (the oldest card in the discard), and the mode lookahead saw the heal as a gain and a card in hand as nothing.
//   · discard pick: an answer (tag 'removal' / 'wipe') first, then the costliest;
//   · mode: an answer is RETURNED to hand; anything else keeps the old choice.
// Fixtures (dictionary-checked): LAW_008 Director Krennic · ASH_019 · ASH_133 Trask Walker (8) · ASH_052 Chimaera (removal) · ASH_116
//   Ant Droid · ASH_097 Moff Gideon · ASH_009 · SOR_095 · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_traskreturn_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-traskreturn') === ['traskreturn'], 'traskreturn is switchable');
$check(in_array('traskreturn', SWUBotFeatureGroups()['p39'] ?? [], true), 'traskreturn is in group p39');
// 8 resources, Trask in hand; my discard $discard (oldest first); their Ahsoka + a Marine. Play Trask, then let the bot answer each prompt.
// Returns [what left the discard, where it went: 'hand' | 'deck', base damage healed].
$run = function (array $discard, string $variant) use ($build, $act, &$gameName) {
    $build(function ($b) use ($discard) {
        $b->MyLeader('LAW_008', false); $b->MyBase('ASH_019', 6); $b->TheirLeader('ASH_009', false);
        $b->FillResourcesForPlayer(1, 'LAW_097', 8);
        $b->WithCardInHandForPlayer(1, 'ASH_133');
        foreach ($discard as $c) $b->WithCardInDiscardForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', false);
    });
    $dmg0 = intval(GetBase(1)[0]->Damage ?? 0);
    $l = SWUBotLegalActions($gameName, 1);
    $play = array_values(array_filter((array)$l['actions'], fn($a) => str_starts_with($a['cardID'], 'myHand-0')))[0] ?? null;
    if ($play === null) return ['no play', '', 0];
    $act(1, intval($play['mode'] ?? 100), strval($play['cardID']));
    for ($k = 0; $k < 4; $k++) {
        $l = SWUBotLegalActions($gameName, 1);
        $tip = strval($l['decisionTooltip'] ?? '');
        if (!str_contains($tip, 'discard') && !str_contains($tip, 'Bottom')) break;
        $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
        $act(1, intval($p['mode'] ?? 100), strval($p['cardID']));
    }
    $left = array_values(array_diff($discard, array_map(fn($o) => strval($o->CardID), array_filter(GetDiscard(1), fn($o) => $o !== null && empty($o->removed)))));
    $inHand = array_map(fn($o) => strval($o->CardID), array_filter(GetHand(1), fn($o) => $o !== null && empty($o->removed)));
    $c = $left[0] ?? '-';
    return [$c, in_array($c, $inHand, true) ? 'hand' : 'deck', $dmg0 - intval(GetBase(1)[0]->Damage ?? 0)];
};
$D = ['ASH_116', 'ASH_097', 'ASH_052'];   // oldest first: Ant Droid, Moff Gideon, Chimaera
// A) Today: the oldest card (Ant Droid) goes to the bottom of the deck, +3 heal.
[$c, $to, $h] = $run($D, 'no-traskreturn');
$check($c === 'ASH_116' && $to === 'deck' && $h === 3, "A fixture: today Ant Droid goes to the bottom and the base heals 3; got $c → $to (+$h)");
// B) Fixed: Chimaera comes back to hand (no heal).
[$c, $to, $h] = $run($D, '');
$check($c === 'ASH_052' && $to === 'hand', "B: Trask returns Chimaera to hand; got $c → $to (+$h)");
// C) No answer in the discard: the costliest unit is taken, and the old mode choice stands (bottom + heal 3, as before).
[$c0, $to0, $h0] = $run(['ASH_116', 'ASH_097'], 'no-traskreturn');
[$c, $to, $h] = $run(['ASH_116', 'ASH_097'], '');
$check($c === 'ASH_097', "C: without an answer, the costliest unit (Moff Gideon) is taken; got $c");
$check($to === $to0, "C: …and its mode is the old choice ($to0); got $to");

bot_test_finish();
