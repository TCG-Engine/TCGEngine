<?php
// LAW_075
// Cost 2 - Interrogation Droid - [Aggression,Cunning,Villainy] - Power 3 - HP 1
// Text: When Played: Exhaust an enemy unit. If you do and that unit costs 3 or less, its controller discards a card from their hand.

// LAW_075 Interrogation Droid — When Played: exhaust an enemy unit. If you do and that unit costs 3 or
// less, its controller discards a card from their hand.
$whenPlayedAbilities["LAW_075:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemy = SWUAllUnits('their');
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Exhaust_an_enemy_unit", "LAW_075#0");
};

$customDQHandlers["LAW_075#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    // ⚠ "ITS CONTROLLER discards" — DETERMINED by the board, so there is NO choice to offer here. This
    // is the other half of the Twin Suns classification: adding a picker would be its own bug.
    // The seat still has to be READ rather than assumed: the untargeted call discarded from
    // OtherPlayer($player), which at four seats is not the controller of the unit just exhausted — a
    // seat-3 unit's exhaust made SEAT 2 discard.
    $ctrl = intval($o->Controller ?? $o->Owner ?? 0);
    // "IF YOU DO" — only when the exhaust changed the unit's state: an already-exhausted unit, or one
    // that can't be exhausted by enemy card abilities (Mythosaur, Kylo Ren's Lightsaber), fails it.
    $wasReady = (intval($o->Status ?? 1) === 1);
    OnExhaustCard(intval($player), $lastDecision);
    if (!$wasReady || intval($o->Status ?? 1) !== 0) return;
    if ($ctrl > 0 && intval(CardCost($o->CardID ?? '')) <= 3) SWUDiscardCards(intval($player), 1, $ctrl);
};
