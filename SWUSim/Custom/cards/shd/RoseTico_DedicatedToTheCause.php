<?php
// SHD_045
// Cost 4 - Rose Tico - Dedicated to the Cause - [Heroism,Vigilance] - Power 2 - HP 6
// Text: Shielded (When you play this unit, give a Shield token to her.) / On Attack: You may defeat a Shield token on a friendly unit. If you do, give 2 Experience tokens to that unit.

// ─── SHD_045 Rose Tico ────────────────────────────────────────────────────────
// Shielded (auto) + On Attack: You may defeat a Shield token on a friendly unit. If you do, give 2
// Experience tokens to that unit.
$onAttackAbilities["SHD_045:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $shields = 0;
            foreach (GetUpgradesOnUnit($o) as $s) { if (($s->CardID ?? '') === 'SOR_T02') $shields++; }
            if ($shields > 0) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Defeat_a_Shield_to_give_2_Experience?", "Choose_a_Shielded_friendly_unit", "SHD_045#0");
};

$customDQHandlers["SHD_045#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    if (!SWUConsumeShieldToken($o)) return;                 // defeat one Shield token
    DoGiveExperienceToken(intval($player), $lastDecision);
    DoGiveExperienceToken(intval($player), $lastDecision);
};
