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
    $defender = SWUCurrentDefendingSeat(intval($player));  // "the defending player" is DETERMINED by the attack, never OtherPlayer()/GetOpponent()
    $playerID = $defender;   // disclose belongs to the DEFENDER; set context for the queued myHand-N picks
    SWUQueueDisclose($defender, ['Vigilance', 'Villainy'], "SEC_038#0",
        "Disclose_VigilanceVillainy_to_give_the_attacker_-6/-0");
    SetSWUVar('SWU_PENDING_DEF_REACTION', '1');
};

$customDQHandlers["SEC_038#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);   // $player = the disclosing DEFENDER
    $atk = GetSWUVar('SWU_CURRENT_ATTACKER', '');
    if ($atk === '' || $atk === null) return;
    // The attacker's mzID is in the ATTACKER's frame ("my…"); re-frame it for the DISCLOSING defender.
    // The old preg_replace my→their is a two-seat flip: above two seats the defender must address the
    // attacker as p{attackerSeat}…, and "their…" would resolve to whichever opponent happens to sit at
    // that index — the deferred-damage frame-mismatch family.
    $atkSeat = intval(GetSWUVar('SWU_CURRENT_ATTACKER_SEAT', '0'));
    if ($atkSeat > 0 && preg_match('/^my([A-Za-z]+)-(\d+)$/', $atk, $mAtk)) {
        $atkDef = SWUForeignMzID(intval($player), $atkSeat, $mAtk[1], intval($mAtk[2]));
    } else {
        $atkDef = preg_replace('/^my/', 'their', $atk);
    }
    AddTurnEffect($atkDef, SWUMakeTurnEffect('SWUDEBUFF', [6, 0], SWU_DUR_ATTACK));
};
