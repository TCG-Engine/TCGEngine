<?php
// SOR_138
// Cost 1 - Force Lightning - [Aggression,Villainy]
// Text: Choose a unit. It loses all abilities for this phase. Then, if you control a FORCE unit, pay any number of resources and deal 2 damage to the chosen unit for each resource paid this way.

// SOR_138 Force Lightning — the chosen unit loses all abilities this phase (TurnEffect marker read by
// LostAbilities); then, if the caster controls a Force unit, offer "pay any number of resources, deal
// 2 per resource" to that same unit.
$customDQHandlers["SOR_138#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    if (!isset($o->TurnEffects) || !is_array($o->TurnEffects)) $o->TurnEffects = [];
    if (!in_array('SOR_138', $o->TurnEffects, true)) $o->TurnEffects[] = 'SOR_138';
    _SWUCheckDefeatAfterAbilityLoss($lastDecision); // SEC_012 Cassian at 0 HP loses initiative-survival → defeated
    if (_SWUControlsForceUnit(intval($player))) {
        $maxX = SWUResourceCount(intval($player), readyOnly: true); // resources only — see SOR_138#1
        if ($maxX > 0) {
            DecisionQueueController::AddDecision($player, "NUMBERCHOOSE", "0|" . $maxX, 1, tooltip:"Pay_any_number_of_resources_(deal_2_damage_each)");
            DecisionQueueController::AddDecision($player, "CUSTOM", "SOR_138#1|" . $lastDecision, 1);
        }
    }
};

$customDQHandlers["SOR_138#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $x = intval($lastDecision);
    if ($x <= 0) return;
    // ⚠ SCALED-EFFECT COST — resources ONLY, never Credit tokens / SEC_122 Droids.
    // The magnitude keys off "resources paid this way", and a Credit is NOT a resource (CR 3.13):
    // defeating one pays 1 less, it does not become a resource paid. So a Credit can pay this CARD's
    // own play cost (the normal play path), but must never scale this effect. Deliberate exception to
    // the engine-wide SWUPayInlineAbilityCost conversion — do not "fix" it back.
    if (!SWUExhaustResources(intval($player), $x)) return;   // pay X (NUMBERCHOOSE was capped at ready)
    $targetMz = $parts[0] ?? '';
    if ($targetMz !== '') SWUDealDamageToUnit($targetMz, 2 * $x, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_138:0"] = function($player, $mzID = '') {
// Force Lightning — "Choose a unit. It loses all abilities for this phase.
                          // Then, if you control a FORCE unit, pay any number of resources and deal 2
                          // damage to the chosen unit for each resource paid this way."
            global $playerID;
            $playerID = intval($player);
            $targets = array_merge(
                ZoneSearch("myGroundArena",    AnyUnitFilter),
                ZoneSearch("mySpaceArena",     AnyUnitFilter),
                ZoneSearch("theirGroundArena", AnyUnitFilter),
                ZoneSearch("theirSpaceArena",  AnyUnitFilter)
            );
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_unit_to_lose_all_abilities_this_phase", "SOR_138#0");
            return;
};
