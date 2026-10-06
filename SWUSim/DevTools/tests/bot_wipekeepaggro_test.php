<?php
// Feature 'wipekeepaggro' (p37) — against an aggro leader, control never resources a relevant WIPE. Owner, Krennic Splash
// questionnaire 2026-10-06 (answer 13): resource "late bombs vs aggro, never the wipes". Traced 20 Krennic Splash vs Ahsoka Blue games
// (2026-10-06): Single Reactor Ignition was RESOURCED 19 times (R1 6, R2 3, R3 6, R4 4), Pre Vizsla 12, Hyperspace Disaster 4. In
// ka006 R3 the bot resourced SRI and kept Annihilator (11). The cause: '_SWUBotResourcing2Tiers' (resourcing3) puts every 7+ card
// pre-flip into the resource-first tier 1 unless it "fits the matchup", and a wipe only fit with 3+ enemy units on board — so in the
// early rounds, when the wipe is being saved FOR the flip turn, it looked like a dead 7-drop. Now a relevant wipe (tag 'wipe',
// covering every arena they use) goes to the keep tier 9. A spare DUPLICATE still goes first (tier 0), as before.
// Fixtures (dictionary-checked): LAW_008 Director Krennic (leader) · LAW_020 Daimyo's Palace · LAW_044 Single Reactor Ignition · ASH_053 Pre Vizsla ·
//   SEC_078 Hyperspace Disaster · JTL_032 Director Krennic (unit) · ASH_097 Moff Gideon · ASH_052 Chimaera · JTL_041 Annihilator · ASH_009 Ahsoka Tano (aggro
//   leader) · HMW_003 Doctor Hemlock (not aggro) · LOF_093 Gungi · ASH_062 · LAW_097 (resources)
//   docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d apc.enable_cli=1 -d xdebug.mode=off SWUSim/DevTools/tests/bot_wipekeepaggro_test.php
require __DIR__ . '/fixtures/bot_test_bootstrap.php';
foreach (['BotLegalActions', 'Custom/BotLookahead', 'Custom/BotEvaluator', 'Rl/CardTags', 'Custom/BotStyles', 'Custom/BotResourcing',
          'Custom/BotGuides', 'Custom/BotFallback', 'Custom/BotRules', 'BotHeuristic'] as $f) include_once "./SWUSim/$f.php";
$check(SWUBotVariantDisabled('no-wipekeepaggro') === ['wipekeepaggro'], 'wipekeepaggro is switchable');
$check(in_array('wipekeepaggro', SWUBotFeatureGroups()['p37'] ?? [], true), 'wipekeepaggro is in group p37');
// ka006 R3: Krennic Splash, 4 resources, this hand; their Ahsoka (or $theirLeader) with Gungi + one more ground unit.
$board = function (array $hand, string $theirLeader = 'ASH_009') use ($build) {
    $build(function ($b) use ($hand, $theirLeader) {
        $b->MyLeader('LAW_008', false); $b->MyBase('LAW_020'); $b->TheirLeader($theirLeader, false);
        $b->WithCurrentRoundBeing(3); $b->FillResourcesForPlayer(1, 'LAW_097', 4);
        foreach ($hand as $c) $b->WithCardInHandForPlayer(1, $c);
        $b->WithGroundUnitForPlayer(2, 'LOF_093', false); $b->WithGroundUnitForPlayer(2, 'ASH_062', false);
    });
};
$pick = function (bool $on) use ($botCtx) {
    SWUBotSetDisabledFeatures($on ? [] : ['wipekeepaggro']);
    $r = SWUBotChooseResourceCards($botCtx('softcontrol'), 1); SWUBotSetDisabledFeatures([]);
    return $r;
};
$KA006 = ['LAW_044', 'JTL_032', 'ASH_097', 'ASH_052', 'JTL_041'];

// A) The traced board: today SRI is resourced; fixed, it is kept (Annihilator, the 11-drop, is the one that goes).
$board($KA006);
$check($pick(false) === ['myHand-0'], 'A fixture: today the bot resources Single Reactor Ignition; got ' . json_encode($pick(false)));
$check($pick(true) !== ['myHand-0'], 'A: SRI stays in hand; got ' . json_encode($pick(true)));
$check($pick(true) === ['myHand-4'], 'A: the late bomb (Annihilator) is resourced instead; got ' . json_encode($pick(true)));
// B) Two SRIs: the spare duplicate may still go (tier 0); one is always kept.
$board(['LAW_044', 'LAW_044', 'JTL_032', 'ASH_097', 'ASH_052']);
$p = $pick(true);
$check($p === ['myHand-1'], 'B: the SECOND SRI (the spare) is the one resourced; got ' . json_encode($p));
// C) Not an aggro leader (a Hemlock mirror): the control tiers do not run — the pick is exactly what it was.
$board($KA006, 'HMW_003');
$check($pick(true) === $pick(false), 'C: vs a non-aggro leader — unchanged; got ' . json_encode($pick(true)) . ' vs ' . json_encode($pick(false)));

// D) Only a RELEVANT wipe is kept: Hyperspace Disaster (space only) against Ahsoka's all-ground board does nothing, so it may still be
// the card resourced — exactly as before.
$board(['SEC_078', 'JTL_032', 'ASH_097', 'ASH_052', 'JTL_041']);
$check($pick(true) === $pick(false) && $pick(true) === ['myHand-0'], 'D: an irrelevant wipe (HSD vs ground) is still resourced; got ' . json_encode($pick(true)));

// E) A BOUNDED wipe is not kept by this rule: Pre Vizsla ("…with a total of 6 or less remaining HP") in the ka006 hand instead of SRI
// is resourced exactly as before (the owner's Q2 ruling resources it vs Vader — bot_owner_resourcing_test).
$board(['ASH_053', 'JTL_032', 'ASH_097', 'ASH_052', 'JTL_041']);
$check($pick(true) === $pick(false), 'E: Pre Vizsla (a bounded wipe) — unchanged; got ' . json_encode($pick(true)) . ' vs ' . json_encode($pick(false)));

bot_test_finish();
