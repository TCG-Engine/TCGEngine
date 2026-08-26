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
    // "…than THE DEFENDING PLAYER" — which does not exist yet: BeginSWUAttack below is what lets the
    // player declare a target. Stamp a marker and let _SWUApplyDefenderConditionalAttackEffects do the
    // comparison once SWU_CURRENT_DEFENDING_SEAT is published. Two seats: identical result.
    // ⚠ GetOpponent() was the worst of the three legacy helpers here: `1→2, 2→1, else NULL`, so at
    // seats 3/4 GetUnitsInPlay(null) counted nothing and the bonus applied unconditionally.
    AddTurnEffect($lastDecision, 'SOR_012_ATK');
    BeginSWUAttack(intval($player), $lastDecision);
};

// SOR_012 IG-88 — Leader Action [Exhaust]: Attack with a unit. If you control more units than
// the defending player, the attacker gets +1/+0 for this attack. The count is deferred to
// _SWUApplyDefenderConditionalAttackEffects (CombatLogic) via the SOR_012_ATK marker, because the
// defending player does not exist until a target has been declared. Deployed side ("each other friendly unit gains Raid 1") is
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
