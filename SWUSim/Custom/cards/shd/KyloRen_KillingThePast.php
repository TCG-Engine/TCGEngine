<?php
// SHD_141
// Cost 6 - Kylo Ren - Killing the Past - [Villainy,Aggression] - Power 6 - HP 7
// Text: While playing this unit, ignore his Villainy aspect penalty if you control Rey. / On Attack: Give a unit +2/+0 for this phase. If it's a non-Villainy unit, also give an Experience token to it.

// ─── SHD_141 Kylo Ren ─────────────────────────────────────────────────────────
// (Aspect-penalty waiver in SWUAspectPenalty.) On Attack: Give a unit +2/+0 for this phase. If it's a
// non-Villainy unit, also give an Experience token to it.
$onAttackAbilities["SHD_141:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Give_a_unit_+2/+0_this_phase_(Exp_if_non-Villainy)?", "Choose_a_unit", "SHD_141#0");
};

$customDQHandlers["SHD_141#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUApplyPhaseBuff($lastDecision, 2, 0, 'SHD_141');
    if (strpos(CardAspect($o->CardID ?? '') ?? '', 'Villainy') === false) {
        DoGiveExperienceToken(intval($player), $lastDecision);
    }
};
