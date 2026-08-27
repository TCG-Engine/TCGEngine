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
    // "If AN enemy base was damaged this phase" — EXISTENTIAL: any opponent's base counts, not seat 2's.
    $dmgdAnyEnemyBase = false;
    foreach (OpponentsOf(intval($player)) as $o) {
        if (GlobalEffectCount(intval($player), 'SWU_DMGBASE_' . $o) > 0) { $dmgdAnyEnemyBase = true; break; }
    }
    if ($dmgdAnyEnemyBase) {
        GiveTokenUpgrade($player, '', [
            'token' => 'ADVANTAGE', 'friendlyOnly' => false,
            'prompt' => "Give_an_Advantage_token_to_a_unit",
        ]);
    }
    if (GlobalEffectCount(intval($player), 'SWU_FRIENDLY_UPGRADE_DEFEATED') > 0) {
        SWUOfferUnitTarget($player, $mzID, [
            'continuation' => 'EXHAUST_UNIT', 'may' => true,
            'question' => "Exhaust_a_unit?", 'prompt' => "Choose_a_unit",
        ]);
    }
};
