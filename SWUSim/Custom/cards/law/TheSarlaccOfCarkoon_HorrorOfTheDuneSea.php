<?php
// LAW_163
// Cost 8 - The Sarlacc of Carkoon - Horror of the Dune Sea - [Command] - Power 8 - HP 9
// Text: On Attack: Put a unit from your discard pile on the bottom of your deck. Deal damage equal to that unit's power to an enemy ground unit.

// LAW_163 The Sarlacc of Carkoon — On Attack: put a unit from your discard on the bottom of your deck;
// deal damage equal to that unit's power to an enemy ground unit.
$onAttackAbilities["LAW_163:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $units = [];
    foreach (ZoneSearch("myDiscard") as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && stripos(CardType($o->CardID ?? '') ?? '', 'Unit') !== false) $units[] = $mz;
    }
    if (empty($units)) return;
    SWUQueueMayChooseTarget(intval($player), $units, "Put_a_unit_from_discard_on_deck_bottom_and_deal_its_power?", "Choose_a_unit", "LAW_163#0");
};

$customDQHandlers["LAW_163#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $power  = intval(CardPower($cardID));
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom(intval($player), [$cardID]);
    if ($power <= 0) return;
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $power, 'side' => 'their', 'arena' => 'Ground',
        'prompt' => "Deal_{$power}_to_an_enemy_ground_unit",
    ]);
};
