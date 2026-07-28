<?php
// LAW_216
// Cost 7 - Jabba's Rancor - Snack Time! - [Cunning,Villainy] - Power 7 - HP 7
// Text: Hidden (This unit can't be attacked if it was played this phase.) / On Attack: An opponent chooses a ground unit they control. You may deal 7 damage to that unit.

// LAW_216 Jabba's Rancor — Hidden + On Attack: an opponent chooses a ground unit they control; you may
// deal 7 damage to that unit. Routed through an intermediate CUSTOM (OnAttack $playerID-restore).
$onAttackAbilities["LAW_216:0"] = function($player, $mzID) {
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_216#0|" . OtherPlayer(intval($player)), 1);
};

$customDQHandlers["LAW_216#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $opp = intval($parts[0] ?? OtherPlayer(intval($player)));
    $playerID = $opp;
    $oppGround = ZoneSearch("myGroundArena", AnyUnitFilter);   // opp's ground (their-frame: my…)
    if (empty($oppGround)) { $playerID = intval($player); return; }
    if (count($oppGround) === 1) {
        $o = GetZoneObject($oppGround[0]);
        $uid = SWUObjUID($o, 0);
        $playerID = intval($player);
        DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Deal_7_damage_to_that_unit?");
        DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_216#2|{$uid}", 1);
        return;
    }
    DecisionQueueController::AddDecision($opp, "MZCHOOSE", implode('&', $oppGround), 1, tooltip: "Choose_a_ground_unit_you_control");
    DecisionQueueController::AddDecision($opp, "CUSTOM", "LAW_216#1|" . intval($player), 1);
};

$customDQHandlers["LAW_216#1"] = function($player, $parts, $lastDecision) {
    // $player = opp (chooser). $lastDecision = chosen ground unit (opp-frame).
    global $playerID;
    $caster = intval($parts[0] ?? OtherPlayer(intval($player)));
    $o = ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') ? GetZoneObject($lastDecision) : null;
    $uid = ($o !== null) ? intval($o->UniqueID ?? 0) : 0;
    if ($uid === 0) return;
    $playerID = $caster;
    DecisionQueueController::AddDecision($caster, "YESNO", "-", 1, tooltip: "Deal_7_damage_to_that_unit?");
    DecisionQueueController::AddDecision($caster, "CUSTOM", "LAW_216#2|{$uid}", 1);
};

$customDQHandlers["LAW_216#2"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) SWUDealDamageToUnit($mz, 7, intval($player));
};
