<?php
// SHD_047
// Cost 5 - The Armorer - Survival Is Strength - [Heroism,Vigilance] - Power 3 - HP 5
// Text: When Played: Give a Shield token to each of up to 3 Mandalorian units.

// ─── SHD_047 The Armorer ──────────────────────────────────────────────────────
// When Played: Give a Shield token to each of up to 3 Mandalorian units.
$whenPlayedAbilities["SHD_047:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $specs = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Mandalorian')) $specs[] = $mz;
        }
    }
    if (empty($specs)) return;
    $max = min(3, count($specs));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode("&", $specs), 1, tooltip:"Give_a_Shield_to_up_to_3_Mandalorian_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_047#0", 1);
};

$customDQHandlers["SHD_047#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode('&', (string)$lastDecision) as $mz) {
        $mz = trim($mz);
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Mandalorian')) {
            DoGiveShieldToken(intval($player), $mz);
        }
    }
};
