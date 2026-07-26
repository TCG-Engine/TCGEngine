<?php
// SHD_007
// Cost 5 - Moff Gideon - Formidable Commander - [Command,Villainy] - Power 3 - HP 6
// Text: Action [exhaust]: Attack with a unit that costs 3 or less. If it's attacking a unit, it gets +1/+0 for this attack.
// DeployText: Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.) / Each friendly unit that costs 3 or less gets +1/+0 and gains Overwhelm while attacking an enemy unit.
// Epic Action: If you control 5 or more resources, deploy this leader. (Flip him, ready him, and move him to the ground arena.)

// ── SHD_007 Moff Gideon ────────────────────────────────────────────────────────
// Front Action [Exhaust]: Attack with a unit that costs 3 or less. If it's attacking a unit, it gets
// +1/+0 for this attack (the vs-unit +1 lives in CombatLogic via the 'SHD_007_FRONT' marker).
// Deployed: Overwhelm (keyword, auto) + ≤3-cost friendly units get +1/+0 & Overwhelm while attacking an
// enemy unit (both combat-time, in CombatLogic).
$leaderAbilities["SHD_007"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $attackers = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1
                && intval(CardCost($o->CardID ?? '')) <= 3) $attackers[] = $mz;
        }
    }
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attackers, "Attack_with_a_unit_that_costs_3_or_less", "SHD_007#front");
};

$customDQHandlers["SHD_007#front"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    AddTurnEffect($lastDecision, 'SHD_007_FRONT');   // +1/+0 while attacking a unit (read in CombatLogic)
    BeginSWUAttack(intval($player), $lastDecision);
};
