<?php
// SEC_005
// Cost 5 - Satine Kryze - Standing on Principles - [Vigilance,Heroism] - Power 0 - HP 8
// Text: Action [Exhaust]: Heal up to 2 damage from a unit. If you do, deal that much damage to your base.
// DeployText: Restore 4
// Epic Action: If you control 5 or more resources, deploy this leader.

$leaderAbilities["SEC_005"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $units = [];
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $units[] = $mz;
        }
    }
    if (empty($units)) { SWUAfterAction($player); return; }   // gate should prevent
    SWUQueueChooseTarget($player, $units, "Heal_up_to_2_damage_from_a_unit", "SEC_005#0");
};

$customDQHandlers["SEC_005#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $lastDecision ?? '';
    $o  = ($mz !== '' && str_contains($mz, '-')) ? GetZoneObject($mz) : null;
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    $uid     = intval($o->UniqueID ?? 0);
    $maxHeal = min(2, intval($o->Damage));
    if ($maxHeal <= 1) {                                  // only 1 healable → no amount choice
        SatineKryzeStandingonPrinciplesApply(intval($player), $uid, $maxHeal);
        SWUAfterAction(intval($player));
        return;
    }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "Heal1&Heal2", 1,
        tooltip: "Heal_up_to_2_(you_then_deal_that_much_to_your_base)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_005#1|{$uid}", 1);
};

$customDQHandlers["SEC_005#1"] = function($player, $parts, $lastDecision) {
    $uid = intval($parts[0] ?? 0);
    $amt = ($lastDecision === 'Heal2') ? 2 : 1;
    SatineKryzeStandingonPrinciplesApply(intval($player), $uid, $amt);
    SWUAfterAction(intval($player));
};

// ── SEC_005 Satine Kryze ──────────────────────────────────────────────────────
// Heal $amt from the unit with UID $uid, then deal the amount actually healed to $player's own base.
function SatineKryzeStandingonPrinciplesApply(int $player, int $uid, int $amt): void {
    global $playerID; $playerID = $player;
    if ($amt <= 0) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null) return;
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    $before = intval($obj->Damage);
    OnHealUnit($player, $mz, $amt);
    $after  = intval(GetZoneObject($mz)->Damage ?? 0);
    $healed = max(0, $before - $after);
    if ($healed > 0) SWUDealDamageToBase($healed, $player);   // "deal that much to YOUR base"
}
