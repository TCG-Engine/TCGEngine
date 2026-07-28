<?php
// LAW_054
// Cost 7 - Maul - Master of the Shadow Collective - [Command,Aggression,Villainy] - Power 6 - HP 8
// Text: Overwhelm / When Attack Ends: If this unit dealt combat damage to a player's base, you may take control of a non-leader unit that player controls. When this unit leaves play, that unit's owner takes control of that unit.

// LAW_054 Maul — step 0: take control of the chosen enemy non-leader unit; link it to Maul so it
// reverts when Maul leaves play (SWU_SEC192 link, source-agnostic revert sweep).
$customDQHandlers["LAW_054#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $maulUID = intval($parts[0] ?? 0);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $stolenUID = intval($o->UniqueID ?? 0);
    $newMz = SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($newMz === '') return;
    AddGlobalEffects(intval($player), "SWU_SEC192|{$maulUID}|{$stolenUID}");
};
