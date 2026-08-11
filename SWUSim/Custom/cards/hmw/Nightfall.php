<?php
// HMW_193
// Cost 2 - Nightfall - [Aggression]
// Text: Deal 1 damage to an enemy unit. / If you control an Endor base, you may attack with a unit. It gets +2/+0 for this attack.

// HMW_193 Nightfall — two INDEPENDENT clauses. They are joined by a full stop, NOT "If you do", so the
// second is not gated on the first finding a target: with no enemy unit on the board the damage simply
// does nothing and the Endor clause still offers its attack.
$whenPlayedAbilities["HMW_193:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);

    // Clause 1 — "Deal 1 damage to an enemy unit." Unqualified by arena (both count) and mandatory.
    $enemies = array_merge(ZoneSearch("theirGroundArena", AnyUnitFilter),
                           ZoneSearch("theirSpaceArena", AnyUnitFilter));
    if (!empty($enemies)) {
        SWUQueueChooseTarget(intval($player), $enemies, "Deal_1_damage_to_an_enemy_unit", "DEAL_UNIT_DAMAGE|1");
    }

    // Clause 2 — gated on controlling an ENDOR base.
    if (!_SWUControlsBaseWithTrait(intval($player), 'Endor')) return;
    // "attack with a unit" carries no "even if it's exhausted" rider (compare TS26_02 Anakin / TS26_04
    // Padmé, which do), and BeginSWUAttack does NOT enforce readiness for an effect-driven attack — so
    // the READY filter has to live here or an exhausted unit could swing.
    $ready = [];
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1) $ready[] = $mz;
        }
    }
    if (empty($ready)) return;
    SWUQueueMayChooseTarget(intval($player), $ready,
        "Attack_with_a_unit_(+2/+0_for_this_attack)?", "Choose_a_unit_to_attack_with", "HMW_193#0");
};

$customDQHandlers["HMW_193#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;   // the event's FINISH_PLAY_CARD owns the close
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    // "+2/+0 for THIS attack" — the one-shot attack bonus consumed in SWUCombatDamage, NOT a phase buff
    // (a phase buff would linger to the regroup; cf. LAW_205 Flash the Vents, same shape).
    SWUAddAttackPowerBonus($lastDecision, 2);
    BeginSWUAttack(intval($player), $lastDecision);   // combat owns the after-action
};
