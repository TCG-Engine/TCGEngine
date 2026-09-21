<?php
// The decision-class diagnostic "@rand:<class>" and the new feature GROUPS (p3a-p3d, wk) — SWUSim/Custom/BotFeatures.php.
// Why: under RANDOM play control beats aggro 51.5%, under the shipped stack ~31%, and three sessions failed to
// localise those points. Each class replaces the stack's pick with a uniform choice among candidates of the SAME
// class and nothing else, so a class where random plays BETTER is a heuristic that mis-serves control.
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_randclass_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/BotHeuristic.php';

$quiet = fn($b) => $b->MyLeader('SOR_014', false, false, true);

// ── registration ───────────────────────────────────────────────────────────────────────────────────
foreach (SWUBotRandomClassList() as $c) {
    $check(SWUBotVariantDisabled("rand:$c") === ["rand:$c"], "@rand:$c is a registered variant");
    $check(isset($GLOBALS['SWUBotChoosers']["heuristic-softcontrol@rand:$c"]), "profile heuristic-softcontrol@rand:$c exists");
}
$check(SWUBotVariantDisabled('rand:nosuchclass') === null, 'an unknown class is refused');
$g = SWUBotFeatureGroups();
$check(count(array_merge($g['p3a'], $g['p3b'], $g['p3c'], $g['p3d'])) === count(SWU_BOT_PART3_FEATURES)
    && array_merge($g['p3a'], $g['p3b'], $g['p3c'], $g['p3d']) === SWU_BOT_PART3_FEATURES,
    'p3a-p3d partition part 3 exactly, in order');
$check($g['wk'] === array_merge(SWU_BOT_PART4_FEATURES, SWU_BOT_PART5_FEATURES, SWU_BOT_PART6_FEATURES, SWU_BOT_PART7_FEATURES),
    'the wk group is everything shipped 2026-09-18/20 (p4+p5+p6+p7)');

// ── the class of a candidate ───────────────────────────────────────────────────────────────────────
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true);
    $b->WithCardInHandForPlayer(1, 'SOR_095'); $b->WithCardInHandForPlayer(1, 'LOF_084');
});
$ctx = $botCtx('softcontrol');
$byClass = [];
foreach ($ctx['actions'] as $a) $byClass[_SWUBotDecisionClass($ctx, $a)][] = strval($a['cardID']);
$check(count($byClass['attacker'] ?? []) === 2, 'free play: two attacks are class "attacker"');
$check(count($byClass['play'] ?? []) === 2, 'free play: two hand plays are class "play"');
$check(count($byClass['tempo'] ?? []) === 2, 'free play: pass and the initiative are class "tempo"');
$raiseAttack(1, 'myGroundArena-0');
$tctx = $botCtx('softcontrol');
$check($tctx['tooltip'] === 'Choose_an_attack_target'
    && _SWUBotDecisionClass($tctx, $tctx['actions'][0]) === 'attacktarget', 'the attack-target prompt is class "attacktarget"');

// ── the randomiser only touches its own class, and only when asked ─────────────────────────────────
$build(function ($b) use ($quiet) {
    $quiet($b); $b->FillResourcesForPlayer(1, 'SOR_095', 6);
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_164', true);
    $b->WithCardInHandForPlayer(1, 'SOR_095'); $b->WithCardInHandForPlayer(1, 'LOF_084');
});
$pickWith = function (string $variant) use (&$gameName) {
    $legal = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$legal['actions'], $legal, $variant);
    return $p === null ? null : strval($p['cardID']);
};
$plain = $pickWith('');
$check($pickWith('') === $plain, 'the stack is deterministic');
SWUBotSetDisabledFeatures([]);
$ctx2 = $botCtx('softcontrol');
$plainClass = null;
foreach ($ctx2['actions'] as $a) { if (strval($a['cardID']) === $plain) $plainClass = _SWUBotDecisionClass($ctx2, $a); }
$check($plainClass !== null && $plainClass !== '', "the stack's pick has a class ($plainClass)");
// A class the pick does NOT belong to must leave it alone.
$other = null;
foreach (SWUBotRandomClassList() as $c) { if ($c !== $plainClass && !empty($byClass[$c])) { $other = $c; break; } }
$check($other !== null && $pickWith("rand:$other") === $plain, "@rand:$other leaves a \"$plainClass\" pick untouched");
// Its OWN class: the result must still be a legal candidate of that class (uniform, deterministic per game).
$r = $pickWith("rand:$plainClass");
$sameClass = [];
foreach ($ctx2['actions'] as $a) { if (_SWUBotDecisionClass($ctx2, $a) === $plainClass) $sameClass[] = strval($a['cardID']); }
$check(in_array($r, $sameClass, true), "@rand:$plainClass returns a candidate of that class");
$check($pickWith("rand:$plainClass") === $r, 'the randomiser is deterministic for a given board (seeded RNG)');
// It must not consume the engine's RNG stream (the counter is restored — see the 'random' chooser's header).
$before = GetDeterministicRandomCounter();
$pickWith("rand:$plainClass");
$check(GetDeterministicRandomCounter() === $before, 'the randomiser is RNG-counter-neutral');

bot_test_finish();
