<?php
// ASH_036
// Cost 3 - Rukh - From the Shadows - [Command,Cunning,Villainy] - Power 1 - HP 5
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / When Attack Ends: If the defending unit was defeated, you may give 3 Advantage tokens to a unit.

// ASH_036 Rukh — When Attack Ends: if the defending unit was defeated, you may give 3 Advantage tokens
// to a unit.
$onAttackEndAbilities["ASH_036:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GetSWUVar('SWU_LAST_DEFENDER_DEFEATED', '') !== '1') return;
    GiveTokenUpgrade($player, '', [
        'token' => 'ADVANTAGE', 'amount' => 3, 'may' => true, 'friendlyOnly' => false,
        'question' => "Give_3_Advantage_tokens_to_a_unit?", 'prompt' => "Choose_a_unit",
    ]);
};
