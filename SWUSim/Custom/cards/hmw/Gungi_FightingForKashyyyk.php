<?php
// HMW_166 Gungi — Unit (Ground) 2/5, cost 3.
// "Grit. When this unit is dealt damage and survives: You may discard a card from your hand. If you do,
//  ready this unit."
// Grit is keyword-wired. The reaction is HMW_211 Tech's self-observer shape, called from _SWUOnUnitDamaged
// (combat and ability damage; prevented damage is never dealt). No card in hand → nothing to offer.

if (!function_exists('_SWUHmw166CheckObserve')) {
    function _SWUHmw166CheckObserve($obj, int $amount): void {
        if ($obj === null || $amount <= 0 || ($obj->CardID ?? '') !== 'HMW_166') return;
        if (LostAbilities($obj)) return;
        $ctrl = intval($obj->Controller ?? 0);
        if ($ctrl <= 0) return;
        DecisionQueueController::AddDecision($ctrl, "CUSTOM", "HMW_166#0|" . intval($obj->UniqueID ?? 0), 1);
    }
}

$customDQHandlers["HMW_166#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUFindMzByUID(intval($parts[0] ?? 0)) === null) return;
    DecisionQueueController::CleanupRemovedCards();
    $hand = array_values(ZoneSearch("myHand"));
    if (empty($hand)) return;
    SWUQueueMayChooseTarget(intval($player), $hand, "Discard_a_card_to_ready_Gungi?", "Discard_a_card_from_your_hand", "HMW_166#1|" . intval($parts[0] ?? 0));
};

$customDQHandlers["HMW_166#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $card = GetZoneObject((string)$lastDecision);
    if (SWUObjGone($card)) return;
    $cid = $card->CardID;
    $card->Remove();
    SWUAddToDiscard(intval($player), $cid, 'HAND');
    DecisionQueueController::CleanupRemovedCards();
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) OnReadyCard(intval($player), $mz);
};
