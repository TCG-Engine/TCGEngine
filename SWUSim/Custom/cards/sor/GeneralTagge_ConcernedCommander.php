<?php
// SOR_080  |  Reprints: SHD_081
// Cost 2 - General Tagge - Concerned Commander - [Command,Villainy] - Power 2 - HP 2
// Text: When Played: Give an Experience token to each of up to 3 TROOPER units.

// SOR_080 General Tagge — When Played: give an Experience token to each of up to
// 3 Trooper units. MZMULTICHOOSE param is "min|max|specs"; result is &-delimited.
$whenPlayedAbilities["SOR_080:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (HasTrait($o->CardID, 'Trooper')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    $targetStr = implode("&", $targets);
    DecisionQueueController::AddDecision($player, "MZMULTICHOOSE", "0|3|" . $targetStr, 1, tooltip:"Give_Experience_to_up_to_3_Trooper_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_080#0", 1);
};

$customDQHandlers["SOR_080#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === "" || $lastDecision === "-" || $lastDecision === "PASS") return;
    foreach (explode("&", $lastDecision) as $mz) {
        if ($mz === "" || $mz === "-" || $mz === "PASS") continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};

// ─── SHD_081 General Tagge ────────────────────────────────────────────────────
// When Played: Give an Experience token to each of up to 3 Trooper units.
$whenPlayedAbilities["SHD_081:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $specs = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Trooper')) $specs[] = $mz;
        }
    }
    if (empty($specs)) return;
    $max = min(3, count($specs));
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|{$max}|" . implode("&", $specs), 1, tooltip:"Give_Experience_to_up_to_3_Trooper_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_081#0", 1);
};

$customDQHandlers["SHD_081#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (explode('&', (string)$lastDecision) as $mz) {
        $mz = trim($mz);
        if ($mz === '' || $mz === '-') continue;
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Trooper')) {
            DoGiveExperienceToken(intval($player), $mz);
        }
    }
};
