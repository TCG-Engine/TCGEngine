<?php
// TWI_137
// Cost 7 - Savage Opress - Monster - [Aggression,Villainy] - Power 7 - HP 7
// Text: When Played: If you control fewer units (including this one) than an opponent, ready this unit.

// TWI_137 Savage Opress — "When Played: If you control fewer units (including this one) than an opponent, ready this unit."
// ⚠ "UNITS" includes LEADER units (a deployed leader is a unit) and tokens — this counted with
// NonLeaderUnitFilter, so an opponent's deployed leader was invisible. ⚠ "AN opponent" is ONE opponent: ready
// if ANY single opponent controls more, never their total — this used their*, which at 3+ seats fans out
// over every opponent and summed them. Both reported 2026-09-30 (game 1438045, 4 seats: P4 vs P1's token +
// deployed Maul). OpponentsOf excludes Team Suns teammates, who are not opponents.
$whenPlayedAbilities["TWI_137:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $myCount = count(SWUControlledUnits(null, AnyUnitFilter));   // "If YOU CONTROL fewer units (including this one)"
    foreach (OpponentsOf(intval($player)) as $opp) {
        $theirs = count(ZoneSearch("p{$opp}GroundArena", AnyUnitFilter)) + count(ZoneSearch("p{$opp}SpaceArena", AnyUnitFilter));
        if ($myCount < $theirs) { OnReadyCard($player, $mzID); return; }
    }
};
