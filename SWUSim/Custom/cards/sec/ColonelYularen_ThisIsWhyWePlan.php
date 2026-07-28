<?php
// SEC_006
// Cost 5 - Colonel Yularen - This Is Why We Plan - [Command,Villainy] - Power 4 - HP 6
// Text: Action [Exhaust]: Attack with a unit. Then, you may attack with another unit that costs less than it.
// DeployText: When this unit completes an attack (and survives): You may attack with another unit that costs 4 or less.
// Epic Action: If you control 5 or more resources, deploy this leader.

// SEC_006 Colonel Yularen (deployed) — When this unit completes an attack (and survives): You may attack
// with another unit that costs 4 or less. Rides the chained-attack resume; combat owns the After Action.
$onAttackEndAbilities["SEC_006:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    SWUQueueAnotherAttack(intval($player), false, true, 0, $uid, 'costle:4');
};

// ── SEC_006 Colonel Yularen ───────────────────────────────────────────────────
// Action [Exhaust]: Attack with a unit. Then, you may attack with another unit that costs less than it.
$leaderAbilities["SEC_006"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $units[] = $mz;
        }
    }
    if (empty($units)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $units, "Attack_with_a_unit", "SEC_006#0");
};

$customDQHandlers["SEC_006#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $uid  = intval($o->UniqueID ?? 0);
    $cost = intval(CardCost($o->CardID));
    // "you may attack with another unit that costs less than it" → costlt:{cost}, may-decline.
    SetSWUVar('SWU_CHAINED_ATTACK', "0,1,0,{$uid},costlt:{$cost}");
    BeginSWUAttack(intval($player), $mz);   // combat owns SWUAfterAction; the chain rides the resume
};
