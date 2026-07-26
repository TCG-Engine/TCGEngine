<?php
// SOR_018
// Cost 6 - Jyn Erso - Resisting Oppression - [Cunning,Heroism] - Power 4 - HP 7
// Text: Action [Exhaust]: Attack with a unit. The defender gets -1/-0 for this attack.
// DeployText: While a friendly unit is attacking, the defender gets -1/-0.
// Epic Action: If you control 6 or more resources, deploy this leader.

// ── SOR_018 Jyn Erso ─────────────────────────────────────────────────────────
// Leader-action follow-up: the chosen attacker is tagged with a one-shot SWU_DEF_DEBUFF_1
// (the defender gets -1/-0 for this attack, consumed in SWUCombatDamage), then the attack
// begins. BeginSWUAttack owns the combat continuation / SWUAfterAction.
$customDQHandlers["SOR_018#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') {
        SWUAfterAction($player);
        return;
    }
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, 'SWU_DEF_DEBUFF_1');
    BeginSWUAttack(intval($player), $lastDecision);
};

// SOR_018 Jyn Erso — Leader Action [Exhaust]: Attack with a unit. The defender gets -1/-0
// for this attack. Choose a friendly READY unit; the SOR_018 handler tags it with a one-shot
// SWU_DEF_DEBUFF_1 (consumed in SWUCombatDamage) and begins the attack.
$leaderAbilities["SOR_018"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    $attackers = array_values(array_filter(array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ), function($mz) { $o = GetZoneObject($mz); return $o !== null && intval($o->Status) === 1; }));
    if (empty($attackers)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget($player, $attackers, 'Attack_with_a_unit_(defender_gets_-1/-0)', 'SOR_018#0');
};
