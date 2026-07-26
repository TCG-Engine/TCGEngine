<?php
// LAW_001
// Cost 6 - Saw Gerrera - Bring Down the Empire - [Command,Aggression] - Power 4 - HP 7
// Text: Action [Exhaust]: Attack with a unit. It gets +2/+0 and gains Overwhelm for this attack. After completing this attack, defeat it.
// DeployText: When Attack Ends: If this unit survived, you may attack with another unit. It gets +2/+0 and gains Overwhelm for this attack. After completing this attack, defeat it.
// Epic Action: If you control 6 or more resources, deploy this leader.

$leaderAbilities["LAW_001"] = function(int $player): void {
    global $playerID; $playerID = $player;
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $targets, "Attack_with_a_unit_(+2/+0,_Overwhelm,_then_defeat_it)", "LAW_001#0");
};

$customDQHandlers["LAW_001#0"] = function($player, $parts, $lastDecision) {
    if (!$lastDecision || !str_contains($lastDecision, '-')) { SWUAfterAction(intval($player)); return; }
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { SWUAfterAction(intval($player)); return; }
    SawGerreraBringDowntheEmpireAttackWith(intval($player), $lastDecision);
};

$onAttackEndAbilities["LAW_001:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;   // Saw didn't survive
    $selfUid = intval($self->UniqueID ?? 0);
    $targets = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUid && intval($o->Status ?? 0) === 1) $targets[] = $mz;
        }
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Attack_with_another_unit_(+2/+0,_Overwhelm,_then_defeat_it)?", "Choose_a_unit", "LAW_001#1");
};

$customDQHandlers["LAW_001#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SawGerreraBringDowntheEmpireAttackWith(intval($player), $lastDecision);
};

// ── LAW_001 Saw Gerrera ───────────────────────────────────────────────────────
// Front Action [Exhaust]: attack with a unit (+2/+0 + Overwhelm this attack; defeat it after).
// Deployed When Attack Ends: if Saw survived, may attack with ANOTHER unit (same grants + self-defeat).
function SawGerreraBringDowntheEmpireAttackWith(int $player, string $attackerMz): void {
    global $playerID; $playerID = $player;
    SWUAddAttackPowerBonus($attackerMz, 2);
    AddTurnEffect($attackerMz, SWUMakeTurnEffect('OVERWHELM', [], SWU_DUR_ATTACK, 'LAW_001'));
    AddTurnEffect($attackerMz, SWUMakeTurnEffect('LAW_062', [], SWU_DUR_ATTACK)); // unconditional self-defeat after attack
    BeginSWUAttack($player, $attackerMz);   // owns SWUAfterAction once it attacks
}
