<?php
// ASH_226
// Cost 7 - Qi'ra - Master of Teräs Käsi - [Cunning] - Power 9 - HP 7
// Text: This unit gets -1/-0 for each card in your hand. / When Played: You may discard a card from your hand. If you do, deal 3 damage to a unit.

// ASH_226 Qi'ra — When Played: you may discard a card from your hand; if you do, deal 3 damage to a unit.
// (The –1/–0-per-hand-card passive lives in ObjectCurrentPower.) Choose the hand card to discard first.
$whenPlayedAbilities["ASH_226:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $hand = [];
    foreach (ZoneSearch("myHand", null) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) $hand[] = $mz;
    }
    if (empty($hand)) return;
    SWUQueueMayChooseTarget(intval($player), $hand, "Discard_a_card_to_deal_3_to_a_unit?", "Choose_a_card_to_discard", "ASH_226#0");
};

$customDQHandlers["ASH_226#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return; // declined
    DoDiscardCard(intval($player), $lastDecision);
    $tg = SWUAllUnits();
    if (empty($tg)) return;   // discarded but no unit to damage → fizzle
    SWUQueueChooseTarget(intval($player), $tg, "Deal_3_damage_to_a_unit", "DEAL_UNIT_DAMAGE|3");
};
