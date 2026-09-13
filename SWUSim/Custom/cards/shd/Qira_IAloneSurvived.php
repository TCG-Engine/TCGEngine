<?php
// SHD_002
// Cost 5 - Qi'ra - I Alone Survived - [Villainy,Vigilance] - Power 0 - HP 8
// Text: Action [1 resource, Exhaust]: Deal 2 damage to a friendly unit. Then, give a Shield token to it.
// DeployText: Grit (This unit gets +1/+0 for each damage on her.) / When Deployed: Heal all damage from each unit. Then, deal damage to each unit equal to half its remaining HP, rounded down.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── SHD_002 Qi'ra ──────────────────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: Deal 2 damage to a friendly unit. Then give a Shield token to it.
// Deployed: Grit (keyword) + When Deployed: Heal all damage from each unit. Then deal each unit damage
// equal to half its remaining HP, rounded down.
$leaderAbilities["SHD_002"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUPayInlineAbilityCost($player, 1)) { SWUAfterAction($player); return; }
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Deal_2_to_a_friendly_unit_then_Shield_it", "SHD_002#0");
    SWUQueueAfterAction($player);
};

$customDQHandlers["SHD_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    // Re-find "it" by UID after the damage: a lethal 2 removes it, and its old mzID would name the next unit.
    $uid = intval($o->UniqueID ?? 0);
    SWUDealDamageToUnit($lastDecision, 2, intval($player));
    $mz = SWUFindMzByUID($uid);                      // shield only if it survived the 2 damage
    if ($mz !== null) DoGiveShieldToken(intval($player), $mz);
};

// When Deployed (deployed side): heal all, then deal each unit floor(remaining HP / 2).
$whenPlayedAbilities["SHD_002:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Heal through OnHealUnit (not a raw Damage = 0) so "when healed" reactions (JTL_062, LAW_047), the
    // healed-this-phase marker (TWI_042) and the heal line + animation all happen. Units are collected by
    // UID first and re-resolved in Qi'ra's frame, so every seat's units are reached.
    $toHeal = [];
    foreach (GetLiveSeatsArray() as $p) {
        foreach (array_merge(GetGroundArena($p) ?? [], GetSpaceArena($p) ?? []) as $u) {
            if (empty($u->removed) && intval($u->Damage ?? 0) > 0) $toHeal[] = [intval($u->UniqueID ?? 0), intval($u->Damage)];
        }
    }
    foreach ($toHeal as [$uid, $amount]) {
        $playerID = intval($player);
        $hmz = SWUFindMzByUID($uid);
        if ($hmz !== null) OnHealUnit(intval($player), $hmz, $amount);   // heal all damage from each unit
    }
    $playerID = intval($player);
    foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if (SWUObjGone($o)) continue;
            $remHP = intval(ObjectCurrentHP($o)) - intval($o->Damage ?? 0);
            $dmg   = intdiv(max(0, $remHP), 2);   // half its remaining HP, rounded down
            if ($dmg > 0) SWUDealDamageToUnit($mz, $dmg, intval($player));
        }
    }
};
