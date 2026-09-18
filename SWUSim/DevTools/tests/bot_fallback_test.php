<?php
// Phase 1a Task 7 — layer 4, the fallback scorer (SWUSim/Custom/BotFallback.php). Asserts the CHOICES,
// not the raw scores. Every decision is a REAL prompt raised by the engine ($raiseAttack / $act), except
// the mulligan: its only producer, QueuePregameSetup(), lives in SWUSim/CreateGame.php, whose top-level code
// loads APIKeys and the database — so that one YESNO is queued exactly as CreateGame.php:311-313 queues it.
// The self-play sweeps (Task 10) run the real pregame.
// Fixtures (dictionary-checked):
//   LOF_084 Knight of Ren 4/4 (3) · SOR_095 Battlefield Marine 3/3 (2) · SOR_046 Consular Security Force 3/7 (4)
//   SOR_164 Wampa 4/5 Overwhelm (4) · SOR_115 Agent Kallus 4/4 Ambush · SOR_073 Moment of Peace (a Shield)
//   SOR_078 Vanquish (defeat a non-leader unit) · JTL_011 Major Vonreg (pilot leader, 4) · SOR_225 TIE/ln
//   Fighter (Vehicle) · LOF_231 Darth Tyranus 4/3 Shielded + Ambush while the Force is with you ·
//   SOR_239 Rebel Pathfinder 2/3
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_fallback_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
include_once './SWUSim/BotLegalActions.php';
include_once './SWUSim/Custom/BotLookahead.php';
include_once './SWUSim/Custom/BotEvaluator.php';
include_once './SWUSim/Rl/CardTags.php';
include_once './SWUSim/Custom/BotStyles.php';
include_once './SWUSim/Custom/BotResourcing.php';
include_once './SWUSim/Custom/BotGuides.php';
include_once './SWUSim/Custom/BotFallback.php';
$ids = fn($acts) => array_map(fn($a) => strval($a['cardID']), $acts);
$sorted = function ($a) { sort($a); return $a; };
$choose = function (string $style) use ($botCtx) {   // filter first, as the stack does
    $c = $botCtx($style); $c['actions'] = SWUBotStyleFilter($c);
    return strval(SWUBotFallbackChoose($c)['cardID'] ?? '');
};

// ── Attack targets ───────────────────────────────────────────────────────────────────────────────
// Control: the bounce target (3/7) is listed FIRST, so first-legal would take it.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'LOF_084', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_046', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$raiseAttack(1, 'myGroundArena-0');
$check($choose('control') === 'theirGroundArena-1', 'Control: a kill-survive target beats a bounce target');

// Control, Marine (2) into a damaged Knight of Ren (3, trade) or the 3/7 (bounce, listed first): trading
// up beats chip damage.
$build(function ($b) {
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_046', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true, 1);
});
$raiseAttack(1, 'myGroundArena-0');
$check($choose('control') === 'theirGroundArena-1', 'Control: a trade into a costlier unit beats a bounce');

// Aggro, Wampa (4, Overwhelm) into an undamaged Marine: the filter keeps both, the base hit (4) wins
// over a kill whose excess is 1.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$raiseAttack(1, 'myGroundArena-0');
$c = $botCtx('aggro');
$check($sorted($ids(SWUBotStyleFilter($c))) === ['theirBase-0', 'theirGroundArena-0'], 'fixture: Aggro keeps the base and the Overwhelm kill');
$check($choose('aggro') === 'theirBase-0', 'Aggro: the base beats a small Overwhelm kill');
// The Marine at 1 HP: the kill plus 3 excess beats the 4-damage base hit.
$build(function ($b) { $b->WithGroundUnitForPlayer(1, 'SOR_164', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true, 2); });
$raiseAttack(1, 'myGroundArena-0');
$check($choose('aggro') === 'theirGroundArena-0', 'Aggro: an Overwhelm kill on a 1-HP unit beats the base');

// ── YESNO: take the optional effect; keep the opening hand ───────────────────────────────────────
$build(function ($b) { $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SOR_115'); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$check($botCtx('normal')['tooltip'] === 'Ambush_attack?', 'fixture: Kallus raises the Ambush YESNO');
$check($choose('normal') === 'YES', 'YESNO: YES');

$build(function ($b) { });
DecisionQueueController::AddDecision(1, "YESNO", "mulligan", 10, tooltip:"Take_a_mulligan_(discard_hand_and_draw_6_new_cards)?");
DecisionQueueController::AddDecision(1, "CUSTOM", "MulliganDecision|1", 10);
$check($choose('aggro') === 'NO', 'the mulligan: NO (keep — mulligans are learned)');

// ── Unit or Pilot: a Pilot goes on a READY Vehicle ───────────────────────────────────────────────
$build(function ($b) { $b->MyLeader('JTL_011'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithSpaceUnitForPlayer(1, 'SOR_225', true); });
$act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
$check($botCtx('normal')['param'] === 'Unit&Pilot', 'fixture: the deploy raises Unit&Pilot');
$check($choose('normal') === 'Pilot', 'a ready Vehicle → Pilot');
$build(function ($b) { $b->MyLeader('JTL_011'); $b->FillResourcesForPlayer(1, 'SOR_095', 4); $b->WithSpaceUnitForPlayer(1, 'SOR_225', false); });
$act(1, 10001, 'myLeader-0!CustomInput!DeployLeader:Unit');
$check($botCtx('normal')['param'] === 'Unit&Pilot' && $choose('normal') === 'Unit', 'only an exhausted Vehicle → Unit');

// ── Continuations ────────────────────────────────────────────────────────────────────────────────
// GIVE_SHIELD over an exhausted Marine (index 0), a ready Marine and an enemy Marine.
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'SOR_073');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', false); $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$act(1, 10002, 'myHand-0!FSM!');
$check(($botCtx('normal')['following'][0] ?? '') === 'GIVE_SHIELD', 'fixture: Moment of Peace offers GIVE_SHIELD');
$check($choose('normal') === 'myGroundArena-1', 'GIVE_SHIELD: the ready friendly unit, over the exhausted one and the enemy');

// DEFEAT_UNIT over a friendly Marine (index 0), an enemy Marine (2) and an enemy Consular Security Force (4).
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 10); $b->WithCardInHandForPlayer(1, 'SOR_078');
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_046', true);
});
$act(1, 10002, 'myHand-0!FSM!');
$check(($botCtx('normal')['following'][0] ?? '') === 'DEFEAT_UNIT', 'fixture: Vanquish offers DEFEAT_UNIT');
$check($choose('control') === 'theirGroundArena-1', 'DEFEAT_UNIT: the most valuable enemy unit, never the friendly one');
// Two identical enemy Marines: a tie goes to the lowest index.
$build(function ($b) {
    $b->FillResourcesForPlayer(1, 'SOR_095', 10); $b->WithCardInHandForPlayer(1, 'SOR_078');
    $b->WithGroundUnitForPlayer(2, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
});
$act(1, 10002, 'myHand-0!FSM!');
$check($ids($botCtx('normal')['actions']) === ['theirGroundArena-0', 'theirGroundArena-1'] && $choose('normal') === 'theirGroundArena-0',
    'a tie goes to the lowest index');

// ── Trigger order: buffs first, except the Tyranus case ──────────────────────────────────────────
// Tyranus's Ambush already kills the 2/3 and survives without the Shield → Ambush first, Shield kept.
$build(function ($b) { $b->WithForceForPlayer(1); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LOF_231'); $b->WithGroundUnitForPlayer(2, 'SOR_239', true); });
$act(1, 10002, 'myHand-0!FSM!');
$types = array_map(fn($a) => strval(GetEffectStack()[intval(substr($a['cardID'], strlen('EffectStack-')))]->TriggerType), $botCtx('normal')['actions']);
$check($botCtx('normal')['tooltip'] === 'Choose_trigger_to_resolve' && $types === ['Shielded', 'Ambush'], 'fixture: Shielded listed before Ambush');
$check($choose('normal') === 'EffectStack-1', 'Tyranus: a kill-survive Ambush goes before the Shield');
// Against a 3/3 the Ambush is only a trade → the Shield goes first.
$build(function ($b) { $b->WithForceForPlayer(1); $b->FillResourcesForPlayer(1, 'SOR_095', 8); $b->WithCardInHandForPlayer(1, 'LOF_231'); $b->WithGroundUnitForPlayer(2, 'SOR_095', true); });
$act(1, 10002, 'myHand-0!FSM!');
$check($choose('normal') === 'EffectStack-0', 'Tyranus: no kill-survive Ambush → the Shield first');
// The engine always lists Shielded first, so the choice above cannot tell "Ambush waits" from a tie
// broken by index — compare the two candidates' scores directly.
$c = $botCtx('normal');
$check(SWUBotScoreAction($c, $c['actions'][1], 1) < SWUBotScoreAction($c, $c['actions'][0], 0), 'an Ambush trigger scores below a buff trigger');

// ── Free play ────────────────────────────────────────────────────────────────────────────────────
// The attack (Marine into a 4/4) now also hits the base — it is no longer a pure losing trade. The leader is
// exhausted so its Action is not on offer.
$build(function ($b) { $b->MyLeader('SOR_014', false); $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true); });
$c = $botCtx('control');
$check($ids($c['actions']) === ['myGroundArena-0!FSM!', 'InitiativeCounter-0!CustomInput!TakeInitiative', 'myHealth-0!CustomInput!Pass'],
    'fixture: attack, initiative, pass');
$check(strval(SWUBotFallbackChoose($c)['cardID']) === 'myGroundArena-0!FSM!',
    'Control: the attack is taken now — it hits the BASE rather than losing a trade');
$check(strval(SWUBotFallbackChoose($botCtx('aggro'))['cardID']) === 'myGroundArena-0!FSM!', 'Aggro: the same attack hits the base → taken');
$check(SWUBotFallbackChoose(array_merge($c, ['actions' => []])) === null, 'an empty candidate list → null');
// The opponent already claimed the initiative: only the attack and Pass remain, and the losing attack
// (listed first) must still lose to passing.
$build(function ($b) {
    $b->MyLeader('SOR_014', false); $b->WithInitiativePlayerBeing(2); $b->WithInitiativeClaimed();
    $b->WithGroundUnitForPlayer(1, 'SOR_095', true); $b->WithGroundUnitForPlayer(2, 'LOF_084', true);
});
$c = $botCtx('control');
$check($ids($c['actions']) === ['myGroundArena-0!FSM!', 'myHealth-0!CustomInput!Pass'], 'fixture: attack, pass (initiative claimed)');
$check(strval(SWUBotFallbackChoose($c)['cardID']) === 'myGroundArena-0!FSM!',
    'Control: attacking the base beats passing — a doomed unit still deals base damage');

// A losing attack is still declined when the base itself is not a legal target: an unconditional Sentinel
// (SOR_063 Cloud City Wing Guard) forces the attack onto the unit, and that attack still loses.
$build(function ($b) {
    $b->MyLeader('SOR_014', false); $b->WithGroundUnitForPlayer(1, 'SOR_227', true); $b->WithGroundUnitForPlayer(2, 'SOR_063', true);
});
$c = $botCtx('control');
$check(strval(SWUBotFallbackChoose($c)['cardID']) !== 'myGroundArena-0!FSM!',
    'Control: with a Sentinel blocking the base, a losing attack is still declined');

// ── "You may": taken by default, declined when it would only hurt me (owner, 2026-09-13) ──────────────
// Daimyo's Palace (LAW_020): "Epic Action: Play a card from your hand, ignoring 1 of its … aspect
// penalties." Marine (Command) costs 2 + 2 penalty here, so the Epic Action is its only way out on 2 resources.
$daimyo = function (string $inHand) {
    return function ($b) use ($inHand) {
        $b->MyLeader('SOR_014', false); $b->MyBase('LAW_020'); $b->FillResourcesForPlayer(1, 'SOR_095', 2);
        $b->WithCardInHandForPlayer(1, $inHand);
    };
};
$build($daimyo('SOR_095'));
$check(SWUBotFallbackChoose($botCtx('control'))['cardID'] === 'myBase-0!CustomInput!EpicAction', 'the Epic Action that plays a card is used');
$act(1, 10001, 'myBase-0!CustomInput!EpicAction');
$check(array_map(fn($o) => $o->CardID, GetGroundArena(1)) === ['SOR_095'], '… and it plays the Marine (a lone playable card is played without a prompt)');
// A genuinely optional prompt behind an unknown continuation: Alliance Dispatcher (SOR_093) "Action [exhaust]:
// Play a unit from your hand. It costs [1 resource] less." Before 2026-09-13 the fallback scored PASS above
// every unscored option and declined this.
$build(function ($b) {
    $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 3);
    $b->WithGroundUnitForPlayer(1, 'SOR_093', true); $b->WithCardInHandForPlayer(1, 'SOR_095');
});
$act(1, 10001, 'myGroundArena-0!CustomInput!Activate');
$c = $botCtx('control');
$check($c['type'] === 'MZMAYCHOOSE' && in_array('PASS', $ids($c['actions']), true) && _SWUBotTooltipEffect($c['tooltip']) === '',
    'fixture: an optional prompt the scorer has no specific rule for');
$check(strval(SWUBotFallbackChoose($c)['cardID']) === 'myHand-0', 'a "you may play a unit" prompt is accepted, not declined');
// Wampa (4) cannot be cast on 2 resources even ignoring a penalty: the Epic Action would do nothing.
$build($daimyo('SOR_164'));
$c = $botCtx('control');
$check(in_array('myBase-0!CustomInput!EpicAction', $ids($c['actions']), true), 'fixture: the Epic Action is still offered');
$check(SWUBotFallbackChoose($c)['cardID'] !== 'myBase-0!CustomInput!EpicAction', 'an Action that would change nothing is never spent (once-per-game Epic kept)');

// Cantina Bouncer (SOR_202): "When Played: You may return a non-leader unit to its owner's hand."
$bouncer = function (bool $enemy) {
    return function ($b) use ($enemy) {
        $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 12); $b->WithCardInHandForPlayer(1, 'SOR_202');
        $b->WithGroundUnitForPlayer(1, 'SOR_095', true);
        if ($enemy) $b->WithGroundUnitForPlayer(2, 'SOR_046', true);
    };
};
$build($bouncer(false)); $act(1, 10002, 'myHand-0!FSM!');
$c = $botCtx('normal');
$check($c['type'] === 'MZMAYCHOOSE' && ($c['following'][0] ?? '') === 'BOUNCE_UNIT', 'fixture: the optional bounce prompt');
$check(strval(SWUBotFallbackChoose($c)['cardID']) === 'PASS', 'a hostile "you may" with only my own units to hit is declined');
$build($bouncer(true)); $act(1, 10002, 'myHand-0!FSM!');
$check(strval(SWUBotFallbackChoose($botCtx('normal'))['cardID']) === 'theirGroundArena-0', '… and with an enemy unit, the enemy is bounced');
$check(_SWUBotTooltipEffect('Defeat_a_friendly_unit_to_create_a_Credit') === 'sacrifice'
    && _SWUBotTooltipEffect('Deal_2_damage_to_a_unit') === 'hostile' && _SWUBotTooltipEffect('Give_a_Shield_token_to_a_unit') === 'beneficial'
    && _SWUBotTooltipEffect('Choose_a_player') === '', 'prompt wording → sacrifice / hostile / beneficial / neither');

// ── Sacrifice costs (Krennic LAW_008: "Action [Exhaust, defeat a friendly unit]: Create a Credit token.") ─
// Only a 4-cost unit to give → never worth one Credit. With Vanguard Infantry (SOR_108, 1 cost, When
// Defeated: give an Experience token) on board → the Action is used and the Infantry is the one given.
$krennic = function (bool $fodder) {
    return function ($b) use ($fodder) {
        $b->MyLeader('LAW_008'); $b->WithGroundUnitForPlayer(1, 'SOR_046', false);
        if ($fodder) $b->WithGroundUnitForPlayer(1, 'SOR_108', false);
    };
};
$build($krennic(false));
$c = $botCtx('control');
$check(in_array('myLeader-0!CustomInput!LeaderAbility', $ids($c['actions']), true), 'fixture: Krennic\'s Action is offered');
$check(SWUBotFallbackChoose($c)['cardID'] !== 'myLeader-0!CustomInput!LeaderAbility', 'Krennic does not sacrifice a 4-cost unit for a Credit');
$build($krennic(true));
$check(SWUBotFallbackChoose($botCtx('control'))['cardID'] === 'myLeader-0!CustomInput!LeaderAbility', 'with cheap When Defeated fodder, the Action is used');
$act(1, 10001, 'myLeader-0!CustomInput!LeaderAbility');
$c = $botCtx('control');
$check(_SWUBotTooltipEffect($c['tooltip']) === 'sacrifice' && $ids($c['actions']) === ['myGroundArena-0', 'myGroundArena-1'], 'fixture: the sacrifice choice');
$check(strval(SWUBotFallbackChoose($c)['cardID']) === 'myGroundArena-1', 'the cheap When Defeated unit is the one sacrificed');

// An OPTIONAL sacrifice behind neutral wording: Chimaera (ASH_052) "You may choose a friendly unit and an
// enemy non-leader unit. If you do, defeat those units." prompts only "Choose_a_friendly_unit". Giving up
// a 4-cost unit (or the Chimaera itself) is declined; cheap When Defeated fodder is taken. SOR_060 Distant
// Patroller (2 cost, space, When Defeated: give a Shield) nets 0.5 — between declining (−1) and neutral (0),
// so the case pins both the −1 PASS and the When Defeated credit.
$chimaera = function (string $mine, bool $space) {
    return function ($b) use ($mine, $space) {
        $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 12); $b->WithCardInHandForPlayer(1, 'ASH_052');
        if ($space) $b->WithSpaceUnitForPlayer(1, $mine, true); else $b->WithGroundUnitForPlayer(1, $mine, true);
        $b->WithGroundUnitForPlayer(2, 'SOR_095', true);
    };
};
$build($chimaera('SOR_046', false)); $act(1, 10002, 'myHand-0!FSM!');
$c = $botCtx('control');
$check($c['tooltip'] === 'Choose_a_friendly_unit' && $c['type'] === 'MZMAYCHOOSE', 'fixture: Chimaera\'s neutral-worded optional pick');
$check(strval(SWUBotFallbackChoose($c)['cardID']) === 'PASS', 'Chimaera: a 4-cost unit is not given up');
$build($chimaera('SOR_060', true)); $act(1, 10002, 'myHand-0!FSM!');
$c = $botCtx('control');
$check(($ids($c['actions'])[0] ?? '') === 'mySpaceArena-0', 'fixture: the Patroller is the first candidate');
$check(strval(SWUBotFallbackChoose($c)['cardID']) === 'mySpaceArena-0', 'Chimaera: cheap When Defeated fodder is given');

// ── Card draw and the deck-out guard (owner, 2026-09-13) ─────────────────────────────────────────────
$decks = function (int $mine, int $theirs, bool $drawCard = false) {
    return function ($b) use ($mine, $theirs, $drawCard) {
        for ($i = 0; $i < $mine; $i++) $b->WithCardInDeckForPlayer(1, 'SOR_095');
        for ($i = 0; $i < $theirs; $i++) $b->WithCardInDeckForPlayer(2, 'SOR_095');
        if ($drawCard) { $b->MyLeader('SOR_014', false); $b->FillResourcesForPlayer(1, 'SOR_095', 1); $b->WithCardInHandForPlayer(1, 'SOR_150'); $b->WithGroundUnitForPlayer(1, 'SOR_095', false); }
    };
};
$build($decks(40, 40)); $check(SWUBotDrawMultiplier(1) === 1.0, 'early game (40 v 40): draw freely');
$build($decks(38, 48)); $check(SWUBotDrawMultiplier(1) === 1.0, 'vs Data Vault early (38 v 48): the buffer still covers the deficit');
$build($decks(20, 30)); $check(SWUBotDrawMultiplier(1) === 0.0, 'owner\'s example (20 v 30): stop seeking extra draw');
$build($decks(11, 5)); $check(SWUBotDrawMultiplier(1) === 0.25, 'under 12 cards, but the opponent decks first: small value');
$build($decks(11, 20)); $check(SWUBotDrawMultiplier(1) === -1.0, 'under 12 cards and I deck first: drawing is a liability');
$scoreDraw = function () use ($botCtx) {
    $c = $botCtx('control');
    foreach ($c['actions'] as $i => $a) { if ($a['cardID'] === 'myHand-0!FSM!') return SWUBotScoreAction($c, $a, $i); }
    return null;
};
$build($decks(40, 40, true)); $early = $scoreDraw();
$build($decks(11, 20, true)); $late = $scoreDraw();
$Wd = SWUBotWeights('control', 1)['draw'];
$check($early !== null && $late !== null && abs(($early - $late) - 2.0 * $Wd) < 1e-9,
    'Heroic Sacrifice (draw) is worth W.draw × 2 less once drawing would deck me out first');

bot_test_finish();
