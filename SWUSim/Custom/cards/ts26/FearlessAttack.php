<?php
// TS26_84
// Cost 4 - Fearless Attack - [Heroism]
// Text: Attack with a unit. It gets +1/+0 for this attack for each unit controlled by the defending player.

// TS26_84 Fearless Attack — the chosen unit attacks with +1/+0 per unit the defending player controls.
// Counted AFTER target declaration (marker + _SWUApplyDefenderConditionalAttackEffects), because at
// declaration time no defender exists — see the family note in CombatLogic.
$customDQHandlers["TS26_84#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (!$lastDecision || !str_contains($lastDecision, '-')) return;
    // "…than THE DEFENDING PLAYER" — which does not exist yet: BeginSWUAttack below is what lets the
    // player declare a target. Stamp a marker and let _SWUApplyDefenderConditionalAttackEffects do the
    // comparison once SWU_CURRENT_DEFENDING_SEAT is published. Two seats: identical result.
    AddTurnEffect($lastDecision, 'TS26_84_ATK');
    BeginSWUAttack(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_84:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status) === 1) $ready[] = $mz;
        }
    }
    if (empty($ready)) return;
    SWUQueueChooseTarget(intval($player), $ready, "Attack_with_a_unit_(+1/+0_per_enemy_unit)", "TS26_84#0");
};
