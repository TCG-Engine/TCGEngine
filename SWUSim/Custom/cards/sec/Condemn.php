<?php
// SEC_038
// Cost 3 - Condemn - [Vigilance,Villainy] - Upgrade Power 0 - Upgrade HP 0
// Text: While attached unit is attacking, it gains: "On Attack: The defending player may disclose VigilanceVillainy. If they do, this unit gets -6/-0 for this attack" and loses all other abilities.

// SEC_038 Condemn — granted "On Attack: the DEFENDING player may disclose VigilanceVillainy → this unit
// gets -6/-0 for this attack." Fires via the onAttackFromUpgrade seam under the ATTACKER ($player); the
// disclose belongs to the DEFENDING (non-active) player, so queue it for them and arm the combat-pause
// (SWU_PENDING_DEF_REACTION) so it resolves before combat damage. The "loses all other abilities" half +
// the multi-Condemn mutual suppression are handled in CombatLogic (BeginSWUAttack marker + the scan gate).
$onAttackAbilities["SEC_038:0"] = function($player, $mzID) {
    global $playerID;
    $defender = OtherPlayer(intval($player));
    $playerID = $defender;   // disclose belongs to the DEFENDER; set context for the queued myHand-N picks
    SWUQueueDisclose($defender, ['Vigilance', 'Villainy'], "SEC_038#0",
        "Disclose_VigilanceVillainy_to_give_the_attacker_-6/-0");
    SetSWUVar('SWU_PENDING_DEF_REACTION', '1');
};

$customDQHandlers["SEC_038#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);   // $player = the disclosing DEFENDER
    $atk = GetSWUVar('SWU_CURRENT_ATTACKER', '');
    if ($atk === '' || $atk === null) return;
    $atkDef = preg_replace('/^my/', 'their', $atk);  // attacker is in its own frame; flip to defender frame
    AddTurnEffect($atkDef, SWUMakeTurnEffect('SWUDEBUFF', [6, 0], SWU_DUR_ATTACK));
};
