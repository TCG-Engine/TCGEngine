<?php
// JTL_139
// Cost 4 - Dengar - Crude and Slovenly - [Aggression,Villainy] - Power 5 - HP 4 - Upgrade Power 1 - Upgrade HP 2
// Text: / Piloting [2 resources Aggression Villainy] / Attached unit gains: "On Attack: Deal 2 indirect damage to a player. If this unit is an Underworld unit, deal 3 indirect damage instead."

// ── JTL_139 Dengar (pilot) — granted "On Attack: Deal 2 indirect to a player (3 if the attached unit is
// an Underworld unit)." ───────────────────────────────────────────────────────────────────────────────
$onAttackAbilities["JTL_139:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $amt = ($host !== null && TraitContains($host, 'Underworld')) ? 3 : 2;
    SWUDealIndirectToChosenPlayer(intval($player), $amt, '', _SWUSrcUID($mzID));
};
