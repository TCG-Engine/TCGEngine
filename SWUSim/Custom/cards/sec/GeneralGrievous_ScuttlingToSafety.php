<?php
// SEC_187
// Cost 2 - General Grievous - Scuttling to Safety - [Cunning,Villainy] - Power 3 - HP 3
// Text: Hidden (This unit can't be attacked if he was played this phase.) / When this unit is attacked: Return him to his owner's hand (before damage is dealt).

// SEC_187 General Grievous — Hidden + On Defense (when this unit is attacked): MANDATORY return him to
// his owner's hand BEFORE damage is dealt. Synchronous (no decision), so the combat-pause isn't needed;
// the bounce removes the target and SWUCombatDamage must no-op on the now-gone defender (the attack
// fizzles → no damage to anyone, no counter). Hidden ("can't be attacked if played this phase") is
// auto-covered by the keyword infra; GIVEN-placed Grievous is attackable so this reaction can fire.
$onDefenseAbilities["SEC_187:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    SWUBounceUnit(intval($player), $mzID);   // returns to $self->Owner's hand
};
