<?php
// LAW_099
// Cost 5 - Governor's Shuttle - [Vigilance,Villainy] - Power 2 - HP 4
// Text: When Played: Each player chooses a unit they control. Defeat those units.

// LAW_099 Governor's Shuttle — When Played: each player chooses a unit they control. Defeat those units.
$whenPlayedAbilities["LAW_099:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $mine = SWUAllUnits('my');
    if (empty($mine)) return;
    SWUQueueChooseTarget(intval($player), $mine, "Choose_a_unit_you_control_to_defeat", "LAW_099#0|" . OtherPlayer(intval($player)));
};

$customDQHandlers["LAW_099#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    $casterUID = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    // Offer the opponent their own unit (their-frame).
    $playerID = $opp;
    $oppUnits = SWUAllUnits('my');
    if (empty($oppUnits)) {
        $playerID = intval($player);
        $mz = SWUFindMzByUID($casterUID);
        if ($mz !== null) SWUDefeatUnit(intval($player), $mz);
        return;
    }
    SWUQueueChooseTarget($opp, $oppUnits, "Choose_a_unit_you_control_to_defeat", "LAW_099#1|" . intval($player) . "|" . $casterUID);
};

$customDQHandlers["LAW_099#1"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $caster    = intval($parts[0] ?? intval($player));
    $casterUID = intval($parts[1] ?? 0);
    $oppO = ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') ? GetZoneObject($lastDecision) : null;
    $oppUID = ($oppO !== null) ? intval($oppO->UniqueID ?? 0) : 0;
    $playerID = $caster;
    $cm = SWUFindMzByUID($casterUID);
    if ($cm !== null) SWUDefeatUnit($caster, $cm);
    if ($oppUID > 0) {
        $om = SWUFindMzByUID($oppUID);
        if ($om !== null) SWUDefeatUnit($caster, $om);
    }
};
