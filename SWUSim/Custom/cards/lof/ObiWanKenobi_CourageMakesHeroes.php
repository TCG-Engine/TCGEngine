<?php
// LOF_008
// Cost 5 - Obi-Wan Kenobi - Courage Makes Heroes - [Command,Heroism] - Power 3 - HP 6
// Text: Action [Exhaust, use the Force (lose your Force token)]: Give an Experience token to a unit without an Experience token on it.
// DeployText: On Attack: You may give an Experience token to another unit without an Experience token on it.
// Epic Action: If you control 5 or more resources, deploy this leader.

// LOF_008 Obi-Wan Kenobi — On Attack: You may give an Experience token to ANOTHER unit without an
// Experience token on it.
$onAttackAbilities["LOF_008:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        $hasExp = false;
        foreach (($o->Subcards ?? []) as $sc) { if (($sc->CardID ?? '') === 'SOR_T01') { $hasExp = true; break; } }
        if (!$hasExp) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), array_values($targets), "Give_an_Experience_token_to_a_unit_without_one?", "Choose_a_unit", "GIVE_EXPERIENCE|1");
};

// LOF_008 Obi-Wan Kenobi — Action [Exhaust, use the Force]: Give an Experience token to a unit without an
// Experience token on it.
$leaderAbilities["LOF_008"] = function(int $player): void {
    global $playerID; $playerID = $player;
    UseTheForce($player);
    $targets = [];
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter),
                         ZoneSearch('theirGroundArena', AnyUnitFilter), ZoneSearch('theirSpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz); if (SWUObjGone($o)) continue;
        $hasExp = false;
        foreach (($o->Subcards ?? []) as $sc) { if (($sc->CardID ?? '') === 'SOR_T01') { $hasExp = true; break; } }
        if (!$hasExp) $targets[] = $mz;
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Give_an_Experience_token_to_a_unit_without_one", "LOF_008#0");
};

$customDQHandlers["LOF_008#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') DoGiveExperienceToken(intval($player), $lastDecision);
    SWUAfterAction(intval($player));
};
