<?php
// SEC_246
// Cost 2 - Contempt for Culture - [Villainy]
// Text: Deal 2 damage to a non-Vehicle unit. / Create a Spy token.

// SEC_246 Contempt for Culture — deal 2 to the chosen non-Vehicle unit, then create a Spy.
$customDQHandlers["SEC_246#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUDealDamageToUnit($lastDecision, 2, intval($player));
    }
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_246:0"] = function($player, $mzID = '') {
// Contempt for Culture — "Deal 2 damage to a non-Vehicle unit. Create a Spy token."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && !HasTrait($o->CardID ?? '', 'Vehicle')) $targets[] = $mz;
            }
            if (empty($targets)) { SWUCreateUnitToken(intval($player), 'SEC_T01'); return; }
            SWUQueueChooseTarget(intval($player), $targets, "Deal_2_damage_to_a_non-Vehicle_unit", "SEC_246#0");
            return;
};
