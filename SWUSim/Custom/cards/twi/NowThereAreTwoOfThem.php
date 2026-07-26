<?php
// TWI_225
// Cost 3 - Now There Are Two of Them - [Cunning]
// Text: If you control exactly one unit, play a non-Vehicle unit from your hand that shares a Trait with the unit you control. It costs 5 resources less.

// TWI_225 Now There Are Two of Them (event continuation) — play the chosen hand unit at -5. Wrap the
// nested play so the inner after-action doesn't double-advance the event.
$customDQHandlers["TWI_225#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, false, 5); // -5 cost
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_225:0"] = function($player, $mzID = '') {
// Now There Are Two of Them — "If you control exactly one unit, play a non-Vehicle
                          // unit from your hand that shares a Trait with the unit you control. It costs 5
                          // resources less."
            global $playerID; $playerID = intval($player);
            $mine = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) { $o = GetZoneObject($mz); if ($o !== null && empty($o->removed)) $mine[] = $o; }
            }
            if (count($mine) !== 1) return; // exactly one unit
            $refTraits = array_filter(array_map('trim', explode(',', CardTrait($mine[0]->CardID ?? '') ?? '')));
            $handUnits = [];
            DecisionQueueController::CleanupRemovedCards();
            foreach (array_values(ZoneSearch('myHand')) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $cid = $o->CardID ?? '';
                if (strpos(CardType($cid) ?? '', 'Unit') === false || HasTrait($cid, 'Vehicle')) continue; // non-Vehicle unit
                $shares = false;
                foreach ($refTraits as $t) { if ($t !== '' && HasTrait($cid, $t)) { $shares = true; break; } }
                if ($shares) $handUnits[] = $mz;
            }
            if (empty($handUnits)) return;
            SWUQueueChooseTarget(intval($player), $handUnits, "Play_a_trait-sharing_non-Vehicle_unit_(-5)", "TWI_225#0");
            return;
};
