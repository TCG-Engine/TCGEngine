<?php
// SHD_046
// Cost 5 - Rey - Keeping the Past - [Heroism,Vigilance] - Power 4 - HP 7
// Text: While playing this unit, ignore her Heroism aspect penalty if you control Kylo Ren. / On Attack: You may heal 2 damage from a unit. If it's a non-Heroism unit, give a Shield token to it.

// ─── SHD_046 Rey ──────────────────────────────────────────────────────────────
// (Aspect-penalty waiver handled in SWUAspectPenalty.) On Attack: You may heal 2 damage from a unit. If
// it's a non-Heroism unit, give a Shield token to it.
$onAttackAbilities["SHD_046:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Heal_2_from_a_unit_(shield_if_non-Heroism)?", "Choose_a_unit_to_heal", "SHD_046#0");
};

$customDQHandlers["SHD_046#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    OnHealUnit(intval($player), $lastDecision, 2);
    if (strpos(CardAspect($o->CardID ?? '') ?? '', 'Heroism') === false) {
        DoGiveShieldToken(intval($player), $lastDecision);
    }
};
