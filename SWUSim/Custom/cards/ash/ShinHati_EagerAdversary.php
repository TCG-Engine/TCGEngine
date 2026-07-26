<?php
// ASH_016
// Cost 6 - Shin Hati - Eager Adversary - [Cunning,Villainy] - Power 4 - HP 6
// Text: When a friendly unit's attack ends: You may exhaust this leader. If you do, exhaust a unit that costs less than the amount of combat damage dealt to a base this attack.
// DeployText: When a friendly unit's attack ends: You may exhaust a unit that costs less than the amount of combat damage dealt to a base this attack. Use this ability only once each round.
// Epic Action: If you control 6 or more resources, deploy this leader.

$customDQHandlers["ASH_016#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;   // declined → use NOT consumed
    $o = GetZoneObject($lastDecision);
    if ($o !== null && empty($o->removed)) {
        $o->Status = 0;                                          // exhaust the cheaper unit
        SWUConsumeUse(SWUGetLeader(intval($player)));            // consume the once-per-round use
    }
};

$customDQHandlers["ASH_016#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (($lastDecision ?? '') !== 'YES') return;
    $leaderArr = &GetLeader(intval($player));
    foreach ($leaderArr as &$l) { if (($l->CardID ?? '') === 'ASH_016' && empty($l->removed)) { $l->Ready = false; break; } }
    unset($l);
    $baseDmg = intval($parts[0] ?? 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) < $baseDmg) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets, "Exhaust_a_unit_costing_less_than_{$baseDmg}", "EXHAUST_UNIT");
};
