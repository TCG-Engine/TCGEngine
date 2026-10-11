<?php
// Feature 'lockpiece' (p35) — an enemy unit whose name-lock holds a card in MY hand is worth more dead. Ninin vs Luke (ASH) Data
// Vault, human game 2026-10-04: R10 SEC_046 Galen Erso named Chimaera ("…loses all abilities") and ASH_077 Ryder Azadi named Pre
// Vizsla ("opponents can't play cards with that name"); Ninin Weakened Galen twice, Ninth Sister killed it, and Chimaera came
// down with its abilities back. Owner 2026-10-04: "it should be valued higher if i have a way to kill them without spending
// resources. this way i can still play the bomb same round."
//   - any kill: + half the locked card's printed cost (feature 'lockpiece', via SWUBotUnitValue);
//   - a FREE kill (an attack) that leaves the locked card castable this round: the full printed cost;
//   - the bomb a Galen blanks waits while a ready unit can kill that Galen by attack this round;
//   - only from the LOCKED seat's view: the lock's own controller never prices it off the opponent's hand.
// Fixtures (dictionary-checked): HMW_003 Doctor Hemlock · HMW_027 Bioweapons Lab · ASH_052 Chimaera (7, Vigilance/Villainy — no
//   penalty under Hemlock) · LAW_149 Rey 9/9 · LOF_100 Kelleran Beq 7/7 (+SOR_T01 Experience = 8/8) · SEC_046 Galen Erso 3/5 (4) ·
//   ASH_077 Ryder Azadi 2/5 (3) · ASH_053 Pre Vizsla (8) · LAW_097 Imperial Door Technician (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_lockpiece_test.php
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
$check(SWUBotVariantDisabled('no-lockpiece') === ['lockpiece'], 'lockpiece is switchable; got ' . json_encode(SWUBotVariantDisabled('no-lockpiece')));
$check(in_array('lockpiece', SWUBotFeatureGroups()['p35'] ?? [], true), 'lockpiece is in group p35');

// Seat 1: Hemlock, Rey ready (or not), $res resources, Chimaera in hand (or not). Seat 2: the lock piece (index 0) naming
// "Chimaera", and Kelleran Beq 8/8 (index 1) — worth more than either lock piece on cost alone.
$board = function (string $lock, string $flag, int $res, bool $chimaera = true, bool $reyReady = true) use ($build) {
    $build(function ($b) use ($lock, $res, $chimaera, $reyReady) {
        $b->MyLeader('HMW_003', false); $b->MyBase('HMW_027');
        $b->FillResourcesForPlayer(1, 'LAW_097', $res);
        $b->WithCardInHandForPlayer(1, $chimaera ? 'ASH_052' : 'ASH_053');   // without Chimaera: Pre Vizsla (8), not the named card
        $b->WithGroundUnitForPlayer(1, 'LAW_149', $reyReady);
        $b->WithGroundUnitForPlayer(2, $lock);
        $b->WithGroundUnitForPlayer(2, 'LOF_100');
        $b->WithUpgradesOnGroundUnitForPlayer(2, 1, [GameStateBuilder::Upgrade('SOR_T01', 2)]);
    });
    $uid = intval(GetGroundArena(2)[0]->UniqueID ?? 0);
    AddGlobalEffects(2, "$flag|$uid|Chimaera");
};
// Rey's attack target, chosen by the whole stack at the real target prompt.
$target = function (string $variant = '') use ($raiseAttack, &$gameName) {
    $raiseAttack(1, 'myGroundArena-0');
    $l = SWUBotLegalActions($gameName, 1);
    $p = SWUBotHeuristicChoose('softcontrol', (array)$l['actions'], $l, $variant);
    return $p === null ? null : strval($p['cardID']);
};

// A) Galen names Chimaera, Chimaera castable after the attack (7 resources, the attack is free): Rey kills Galen.
$board('SEC_046', 'SWU_GALEN', 7);
$check(_SWUGalenNames(1, 'Chimaera'), 'A fixture: Galen\'s lock on Chimaera is live');
$check($target('no-lockpiece') === 'theirGroundArena-1', 'A fixture: today Rey kills the bigger Kelleran; got ' . var_export($target('no-lockpiece'), true));
$board('SEC_046', 'SWU_GALEN', 7);
$check($target() === 'theirGroundArena-0', 'A: a free kill that frees a castable Chimaera — Rey kills Galen; got ' . var_export($target(), true));

// B) Same board, 6 resources: Chimaera is NOT castable this round — only the half premium, and Kelleran (8) is worth more.
$board('SEC_046', 'SWU_GALEN', 6);
$check($target() === 'theirGroundArena-1', 'B: Chimaera not castable this round — Kelleran is still the target; got ' . var_export($target(), true));

// C) Galen named Chimaera but my hand holds Pre Vizsla instead (a pricier card, not the named one): no premium at all.
$board('SEC_046', 'SWU_GALEN', 7, false);
$check($target() === 'theirGroundArena-1', 'C: the hand card is not the named one — Kelleran; got ' . var_export($target(), true));

// D) Ryder Azadi's play-block (SWU_NAMEBLOCK) is a lock too.
$board('ASH_077', 'SWU_NAMEBLOCK', 7);
$check(SWUCardPlayBlocked(1, 'ASH_052'), 'D fixture: Ryder blocks Chimaera');
$check($target() === 'theirGroundArena-0', 'D: Rey kills Ryder to free Chimaera; got ' . var_export($target(), true));

// E) The viewer: only the locked seat prices the lock. Seat 2 valuing its own Galen never reads seat 1's hand.
$board('SEC_046', 'SWU_GALEN', 7);
$galen = SWUBotUnits(2)[0];
$GLOBALS['SWUBotViewerSeat'] = 1; $as1 = SWUBotUnitValue($galen);
$GLOBALS['SWUBotViewerSeat'] = 2; $as2 = SWUBotUnitValue($galen);
unset($GLOBALS['SWUBotViewerSeat']);
$check($as1 > $as2, "E: seat 1 (locked) values Galen above seat 2's own view; got $as1 vs $as2");
$GLOBALS['SWUBotDisabledFeatures'] = ['lockpiece']; $GLOBALS['SWUBotViewerSeat'] = 2; $off = SWUBotUnitValue($galen);
$GLOBALS['SWUBotDisabledFeatures'] = []; unset($GLOBALS['SWUBotViewerSeat']);
$check(abs($as2 - $off) < 1e-9, "E: seat 2's view of its own Galen is unchanged by the feature; got $as2 vs $off");

// F) Ordering: Galen blanks Chimaera and Rey can kill Galen this round — the free play attacks, it does not cast the blank bomb.
$board('SEC_046', 'SWU_GALEN', 7);
$l = SWUBotLegalActions($gameName, 1);
$play = null; foreach ((array)$l['actions'] as $a) if (str_starts_with(strval($a['cardID']), 'myHand-0!')) $play = $a;
$ctx = $botCtx('softcontrol');
$GLOBALS['SWUBotDisabledFeatures'] = ['lockpiece']; $sOff = SWUBotScoreAction($ctx, $play, 0);
$GLOBALS['SWUBotDisabledFeatures'] = []; $sOn = SWUBotScoreAction($ctx, $play, 0);
$check($play !== null && $sOff > 0, "F fixture: today casting Chimaera scores positive; got $sOff");
$check($sOn < 0, "F: the blanked Chimaera waits for the free kill on Galen; got $sOn");
// …and with Rey exhausted there is no free kill this round: the play is scored as before.
$board('SEC_046', 'SWU_GALEN', 7, true, false);
$l = SWUBotLegalActions($gameName, 1);
$play = null; foreach ((array)$l['actions'] as $a) if (str_starts_with(strval($a['cardID']), 'myHand-0!')) $play = $a;
$ctx = $botCtx('softcontrol');
$GLOBALS['SWUBotDisabledFeatures'] = ['lockpiece']; $sOff = SWUBotScoreAction($ctx, $play, 0);
$GLOBALS['SWUBotDisabledFeatures'] = []; $sOn = SWUBotScoreAction($ctx, $play, 0);
$check(abs($sOn - $sOff) < 1e-9, "F: no ready killer — the play is unchanged; got $sOn vs $sOff");

bot_test_finish();
