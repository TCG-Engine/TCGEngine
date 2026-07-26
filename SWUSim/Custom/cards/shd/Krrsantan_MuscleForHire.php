<?php
// SHD_139
// Cost 5 - Krrsantan - Muscle for Hire - [Villainy,Aggression] - Power 3 - HP 7
// Text: When Played: If an enemy unit has a Bounty, you may ready this unit. / On Attack: Choose a ground unit. You may deal 1 damage to it for each damage on this unit.

// ─── SHD_139 Krrsantan ────────────────────────────────────────────────────────
// When Played: If an enemy unit has a Bounty, you may ready this unit.
// On Attack: Choose a ground unit. You may deal 1 damage to it for each damage on this unit.
$whenPlayedAbilities["SHD_139:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $has = false;
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && ObjectHasBounty($o) > 0) { $has = true; break 2; }
        }
    }
    if (!$has) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, tooltip:"Ready_Krrsantan?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "SHD_139#ready|{$uid}", 1);
};

$customDQHandlers["SHD_139#ready"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision !== 'YES') return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};

$onAttackAbilities["SHD_139:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $n = $self ? intval($self->Damage ?? 0) : 0;
    if ($n <= 0) return;                                           // 0 damage → nothing to deal
    $uid = SWUObjUID($self, 0);
    $targets = [];
    foreach (['myGroundArena', 'theirGroundArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    SWUQueueMayChooseTarget(intval($player), $targets,
        "Deal_{$n}_damage_to_a_ground_unit?", "Choose_a_ground_unit", "SHD_139#0|{$uid}");
};

$customDQHandlers["SHD_139#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $selfMz = SWUFindMzByUID(intval($parts[0] ?? 0));
    $self   = $selfMz ? GetZoneObject($selfMz) : null;
    $n = $self ? intval($self->Damage ?? 0) : 0;
    if ($n <= 0) return;
    SWUDealDamageToUnit($lastDecision, $n, intval($player));
};
