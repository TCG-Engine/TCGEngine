<?php
// ASH_102
// Cost 9 - Ravager - Final Imperial Command - [Command,Villainy] - Power 8 - HP 10
// Text: Restore 2 / When you play a unit: You may have it deal damage equal to its power to a unit in the same arena.

// ASH_102 Ravager — continuation: the played unit (dealer) deals its power to the chosen unit.
$customDQHandlers["ASH_102#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $dealerMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($dealerMz === null) return;
    $dealer = GetZoneObject($dealerMz);
    if (SWUObjGone($dealer)) return;
    $pow = intval(ObjectCurrentPower($dealer));
    if ($pow <= 0) return;
    // The dealer is the damage's SOURCE (CR 18.2a) — ASH_196 Gorian's unpreventable, SEC_050, LOF_108, HMW_185 read it (2026-10-07).
    SWUDealDamageToUnit($lastDecision, $pow, intval($player), $dealerMz);
};
