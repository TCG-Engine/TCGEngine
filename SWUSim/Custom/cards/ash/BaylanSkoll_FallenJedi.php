<?php
// ASH_039
// Cost 6 - Baylan Skoll - Fallen Jedi - [Aggression,Cunning,Villainy] - Power 6 - HP 6
// Text: Overwhelm / When Played/When Attack Ends: If an enemy base was damaged this phase, give an Advantage token to a unit. If a friendly upgrade was defeated this phase, you may exhaust a unit.

// ASH_039 Baylan Skoll — Overwhelm + "When Played/When Attack Ends: if an enemy base was damaged this
// phase, give an Advantage token to a unit. If a friendly upgrade was defeated this phase, you may exhaust
// a unit." Two independent conditional effects, both windows. (Friendly-upgrade flag set by _SWUOnUpgradeDefeated.)
$whenPlayedAbilities["ASH_039:0"] =
$onAttackEndAbilities["ASH_039:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $opp = OtherPlayer(intval($player));
    if (GlobalEffectCount(intval($player), 'SWU_DMGBASE_' . $opp) > 0) {
        $tg = SWUAllUnits();
        if (!empty($tg)) SWUQueueChooseTarget(intval($player), $tg, "Give_an_Advantage_token_to_a_unit", "GIVE_ADVANTAGE|1");
    }
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_UPGRADE_DEFEATED') > 0) {
        $tg2 = SWUAllUnits();
        if (!empty($tg2)) SWUQueueMayChooseTarget(intval($player), $tg2, "Exhaust_a_unit?", "Choose_a_unit", "EXHAUST_UNIT");
    }
};
