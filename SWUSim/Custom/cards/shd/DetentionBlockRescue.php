<?php
// SHD_180
// Cost 3 - Detention Block Rescue - [Aggression]
// Text: Deal 3 damage to a unit. If that unit is guarding any captured cards, deal 6 damage instead.

// ─── SHD_180 Detention Block Rescue (Event) continuation ──────────────────────
// Deal 3 damage to the chosen unit; 6 instead if it is guarding any captured cards.
$customDQHandlers["SHD_180#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $guarding = false;
    if (is_array($o->Subcards ?? null)) {
        foreach ($o->Subcards as $sub) {
            $isCaptive = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
            $isRemoved = is_array($sub) ? !empty($sub['removed'])   : !empty($sub->removed);
            if ($isCaptive && !$isRemoved) { $guarding = true; break; }
        }
    }
    SWUDealDamageToUnit($lastDecision, $guarding ? 6 : 3, intval($player));
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_180:0"] = function($player, $mzID = '') {
// Detention Block Rescue — "Deal 3 damage to a unit. If that unit is guarding any
                          // captured cards, deal 6 damage instead."
            $targets = SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Deal_3_(6_if_guarding_captives)_to_a_unit", "SHD_180#0");
            return;
};
