<?php
// LAW_174
// Cost 3 - 0-0-0 - Translation and Torture - [Aggression,Villainy] - Power 4 - HP 4
// Text: On Attack: You may put a Aggression card from your discard pile on the bottom of your deck. If you do, deal 1 damage to each enemy base.

// LAW_174 0-0-0 — On Attack: you may put an Aggression card from your discard on the bottom of your
// deck. If you do, deal 1 damage to each enemy base.
$onAttackAbilities["LAW_174:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $agg = [];
    foreach (ZoneSearch("myDiscard") as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && strpos((string)(CardAspect($o->CardID ?? '') ?? ''), 'Aggression') !== false) $agg[] = $mz;
    }
    if (empty($agg)) return;
    SWUQueueMayChooseTarget(intval($player), $agg, "Put_an_Aggression_card_on_deck_bottom_(deal_1_to_each_enemy_base)?", "Choose_a_card", "LAW_174#0");
};

$customDQHandlers["LAW_174#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cardID = $o->CardID;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    _topDeckPutRemainingToBottom(intval($player), [$cardID]);
    SWUDealDamageToBase(1, OtherPlayer(intval($player)));
};
