<?php
// SEC_002
// Cost 5 - Jabba the Hutt - Wonderful Human Being - [Vigilance,Villainy] - Power 2 - HP 8
// Text: Action [1 resource, Exhaust]: A friendly damaged unit deals 1 damage to an enemy unit. If the friendly unit has 3 or more damage on it, it deals 2 damage instead.
// DeployText: When another friendly unit is dealt damage and survives: You may have that unit deal that much damage to an enemy unit. Use this ability only once each round.
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── SEC_002 Jabba the Hutt ────────────────────────────────────────────────────
// Leader Action [1 resource, Exhaust]: A friendly damaged unit deals 1 damage to an enemy unit. If the
// friendly unit has 3 or more damage on it, it deals 2 damage instead. (Gate ensures both exist.)
$leaderAbilities["SEC_002"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (!SWUExhaustResources($player, 1)) { SWUAfterAction($player); return; }
    $sources = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Damage) > 0) $sources[] = $mz;
        }
    }
    if (empty($sources)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $sources, "Choose_a_friendly_damaged_unit", "SEC_002#0");
};

$customDQHandlers["SEC_002#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $srcMz = $lastDecision ?? '';
    $src   = ($srcMz !== '' && str_contains($srcMz, '-')) ? GetZoneObject($srcMz) : null;
    if (SWUObjGone($src)) { SWUAfterAction(intval($player)); return; }
    $amount  = intval($src->Damage) >= 3 ? 2 : 1;   // 2 if the friendly unit has 3+ damage, else 1
    $enemies = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed)) $enemies[] = $mz;
        }
    }
    if (empty($enemies)) { SWUAfterAction(intval($player)); return; }
    SWUQueueChooseTarget(intval($player), $enemies, "Deal_{$amount}_damage_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|{$amount}");
    SWUQueueAfterAction(intval($player));
};
