<?php
// SOR_012
// Cost 5 - IG-88 - Ruthless Bounty Hunter - [Aggression,Villainy] - Power 5 - HP 4
// Text: Action [Exhaust]: Attack with a unit. If you control more units than the defending player, the attacker gets +1/+0 for this attack.
// DeployText: Each other friendly unit gains Raid 1. (They get +1/+0 while attacking.)
// Epic Action: If you control 5 or more resources, deploy this leader.

// ── SOR_012 IG-88 ────────────────────────────────────────────────────────────
// Leader-action follow-up: if the controller has more units in play than the opponent, the
// chosen attacker gets a one-shot +1/+0 ("for this attack"); then begin the attack.
$customDQHandlers["SOR_012#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    $opp = GetOpponent(intval($player));
    if (count(GetUnitsInPlay(intval($player))) > count(GetUnitsInPlay($opp))) {
        SWUAddAttackPowerBonus($lastDecision, 1);
    }
    BeginSWUAttack(intval($player), $lastDecision);
};

// SOR_012 IG-88 — Leader Action [Exhaust]: Attack with a unit. If you control more units than
// the defending player, the attacker gets +1/+0 for this attack. (Defending player is always the
// opponent in a 2-player game, so the count condition is resolved in the SOR_012 handler before
// the attack target is even chosen.) Deployed side ("each other friendly unit gains Raid 1") is
// already implemented in KeywordEffects.php.
$leaderAbilities["SOR_012"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $attackers = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }));
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attackers, 'Attack_with_a_unit', 'SOR_012#0');
};
