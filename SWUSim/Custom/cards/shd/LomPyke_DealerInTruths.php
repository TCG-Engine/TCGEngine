<?php
// SHD_032
// Cost 4 - Lom Pyke - Dealer in Truths - [Villainy,Vigilance] - Power 4 - HP 6
// Text: On Attack: You may give a Shield token to an enemy unit. If you do, give a Shield token to a friendly unit. / Smuggle [5 resources Vigilance Villainy]

// ─── SHD_032 Lom Pyke ─────────────────────────────────────────────────────────
// On Attack: You may give a Shield token to an enemy unit. If you do, give a Shield token to a
// friendly unit. MZMAYCHOOSE is the OnAttack-safe optional pick; the friendly follow-up is a
// mandatory choose queued from the CUSTOM continuation (safe from the OnAttack $playerID-restore
// skip). "A friendly unit" includes Lom Pyke himself — never empty while he attacks.
$onAttackAbilities["SHD_032:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $enemies[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $enemies,
        "Give_a_Shield_token_to_an_enemy_unit?", "Give_a_Shield_token_to_an_enemy_unit", "SHD_032#0");
};

$customDQHandlers["SHD_032#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    DoGiveShieldToken(intval($player), $lastDecision);
    $friendly = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $friendly[] = $mz;
        }
    }
    SWUQueueChooseTarget(intval($player), $friendly,
        "Give_a_Shield_token_to_a_friendly_unit", "GIVE_SHIELD");
};
