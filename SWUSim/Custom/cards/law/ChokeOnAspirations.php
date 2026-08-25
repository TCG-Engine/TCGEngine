<?php
// LAW_102
// Cost 1 - Choke on Aspirations - [Vigilance,Villainy]
// Text: Deal up to 5 damage to a friendly non-Vehicle unit. If it survives, heal damage from your base equal to the damage dealt this way.

// LAW_102 Choke on Aspirations — step 0: ask how much (0..5) to deal to the chosen friendly non-Vehicle.
$customDQHandlers["LAW_102#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|5", 1, "Deal_how_much_(up_to_5)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_102#1|" . $lastDecision, 1);
};

// LAW_102 step 1: deal the chosen amount; if the unit survives, heal that much from the caster's base.
$customDQHandlers["LAW_102#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $mz = $parts[0] ?? '';
    if ($mz === '') return;
    $o = GetZoneObject($mz);
    if (SWUObjGone($o)) return;
    $amount = max(0, min(5, intval($lastDecision)));
    if ($amount <= 0) return;
    $uid = intval($o->UniqueID ?? 0);
    $before = intval($o->Damage ?? 0);
    SWUDealDamageToUnit($mz, $amount, intval($player));
    // "If it survives" — still in play after the damage.
    $stillMz = SWUFindMzByUID($uid);
    if ($stillMz === null) return;                   // defeated → no heal
    $after = intval(GetZoneObject($stillMz)->Damage ?? $before);
    $dealt = max(0, $after - $before);               // actual damage dealt (respects shields)
    if ($dealt > 0) OnHealBase(intval($player), intval($player), $dealt);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_102:0"] = function($player, $mzID = '') {
// Choke on Aspirations — "Deal up to 5 damage to a friendly non-Vehicle unit.
                          // If it survives, heal damage from your base equal to the damage dealt this way."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUFriendlyUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !HasTrait($o->CardID ?? '', 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_friendly_non-Vehicle_unit", "LAW_102#0");
            return;
};
