<?php
// TWI_013
// Cost 7 - Mace Windu - Vaapad Form Master - [Aggression,Heroism] - Power 5 - HP 8
// Text: Action [1 resource, Exhaust]: Deal 1 damage to a damaged enemy unit. Then, if it has 5 or more damage on it, deal 1 damage to it.
// DeployText: When Deployed: Deal 2 damage to each damaged enemy unit.
// Epic Action: If you control 7 or more resources, deploy this leader.

// TWI_013 Mace Windu (front continuation) — deal 1 to the chosen damaged enemy; if it then has 5+ damage,
// deal 1 more.
$customDQHandlers["TWI_013#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    global $playerID; $playerID = intval($player);
    $uid = 0; $o = GetZoneObject($lastDecision); if ($o !== null) $uid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 1, intval($player));
    $mz = SWUFindMzByUID($uid);
    if ($mz !== null) { $o2 = GetZoneObject($mz); if ($o2 !== null && empty($o2->removed) && intval($o2->Damage ?? 0) >= 5) SWUDealDamageToUnit($mz, 1, intval($player)); }
    SWUAfterAction(intval($player));
};

// TWI_013 Mace Windu (deployed) — "When Deployed: Deal 2 damage to each damaged enemy unit."
$whenPlayedAbilities["TWI_013:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $uids = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $uids[] = intval($o->UniqueID ?? 0);
        }
    }
    foreach ($uids as $uid) { $mz = SWUFindMzByUID($uid); if ($mz !== null) SWUDealDamageToUnit($mz, 2, intval($player)); }
};

// TWI_013 Mace Windu (front) — "Action [1 resource, Exhaust]: Deal 1 damage to a damaged enemy unit.
// Then, if it has 5 or more damage on it, deal 1 damage to it." (Resource + damaged-enemy gated.)
$leaderActionResourceCosts["TWI_013"] = 1;

$leaderAbilities["TWI_013"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 1))) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage ?? 0) > 0) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_1_to_a_damaged_enemy_unit_(then_1_more_if_5+_damage)", "TWI_013#0");
};
