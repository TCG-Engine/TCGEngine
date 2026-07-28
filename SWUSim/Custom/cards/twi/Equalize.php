<?php
// TWI_055
// Cost 3 - Equalize - [Vigilance,Vigilance]
// Text: Give a unit -2/-2 for this phase. Then, if you control fewer units than that unit's controller, give another unit -2/-2 for this phase.

// TWI_055 Equalize — apply -2/-2 to the first chosen unit; then, if the caster controls FEWER units
// than that unit's controller, offer a second unit -2/-2.
$customDQHandlers["TWI_055#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $firstUID = intval($o->UniqueID ?? -1);
    $tgtCtrl  = intval($o->Controller ?? 0);
    SWUApplyPhaseDebuff($lastDecision, 2, 2, 'TWI_055');
    if ($tgtCtrl <= 0) return;
    $myCount = count(GetUnitsInPlay(intval($player)));
    $tgtCount = count(GetUnitsInPlay($tgtCtrl));
    if ($myCount >= $tgtCount) return;   // only if you control FEWER units than that unit's controller
    // Offer a second (different) unit -2/-2.
    SWUOfferUnitTarget(intval($player), $lastDecision, [
        'continuation' => 'APPLY_PHASE_DEBUFF|2|2|TWI_055', 'side' => 'any', 'excludeUID' => $firstUID,
        'prompt' => "Give_another_unit_-2/-2_for_this_phase",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_055:0"] = function($player, $mzID = '') {
// Equalize — "Give a unit -2/-2 for this phase. Then, if you control fewer units
                          // than that unit's controller, give another unit -2/-2 for this phase."
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'TWI_055#0', 'side' => 'any',
                'prompt' => "Give_a_unit_-2/-2_for_this_phase",
            ]);
            return;
};
