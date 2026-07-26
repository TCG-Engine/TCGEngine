<?php
// SHD_049
// Cost 6 - The Mandalorian - Wherever I Go, He Goes - [Vigilance,Heroism] - Power 5 - HP 6
// Text: Sentinel / When Played: You may heal all damage from a unit that costs 2 or less and give 2 Shield tokens to it.

// ─── SHD_049 The Mandalorian ──────────────────────────────────────────────────
// Sentinel (auto) + When Played: You may heal ALL damage from a unit that costs 2 or less and give 2
// Shield tokens to it.
$whenPlayedAbilities["SHD_049:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID)) <= 2) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Heal_all_damage_and_give_2_Shields_to_a_cheap_unit?", "Choose_a_unit_costing_2_or_less", "SHD_049#0");
};

$customDQHandlers["SHD_049#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $dmg = intval($o->Damage ?? 0);
    if ($dmg > 0) OnHealUnit(intval($player), $lastDecision, $dmg);   // heal ALL damage
    DoGiveShieldToken(intval($player), $lastDecision);
    DoGiveShieldToken(intval($player), $lastDecision);
};
