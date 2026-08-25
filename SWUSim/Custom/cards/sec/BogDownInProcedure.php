<?php
// SEC_234
// Cost 3 - Bog Down in Procedure - [Cunning]
// Text: Exhaust a unit. / You may disclose Cunning (reveal a card from your hand with this aspect icon). If you do, exhaust another unit.

// SEC_234 Bog Down in Procedure — #0: exhaust the chosen unit, then offer the Cunning disclose;
// #1: exhaust ANOTHER unit.
$customDQHandlers["SEC_234#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $firstUID = intval($o->UniqueID ?? 0);
    OnExhaustCard(intval($player), $lastDecision);
    SWUQueueDisclose(intval($player), ['Cunning'], "SEC_234#1|{$firstUID}",
        "Disclose_Cunning_to_exhaust_another_unit");
};

$customDQHandlers["SEC_234#1"] = function($player, $parts, $lastDecision) {
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'EXHAUST_UNIT', 'excludeUID' => intval($parts[0] ?? 0), // "another unit"
        'prompt' => "Exhaust_another_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_234:0"] = function($player, $mzID = '') {
// Bog Down in Procedure — "Exhaust a unit. You may disclose Cunning →
                          // exhaust another unit."
            global $playerID; $playerID = intval($player);
            $units = SWUAllUnits();
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Exhaust_a_unit", "SEC_234#0");
            return;
};
