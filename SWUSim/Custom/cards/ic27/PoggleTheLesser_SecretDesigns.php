<?php
// IC27_145
// Cost 4 - Poggle the Lesser - Secret Designs - [Cunning,Villainy] - Unit (Ground) 3/5 (unique)
//   Traits: Separatist, Official
// Text: On Attack: You may pay [1 resource] and discard a card from your hand. If you do, the next card
//       you play this phase costs [3 resources] less.
//
// pay? (YESNO) -> pay 1 -> which card (mandatory MZCHOOSE, queued from the CUSTOM so the On Attack
// $playerID-restore cannot skip it) -> discard -> arm the charge. Nothing rides a transient global: the
// discount is a GlobalEffects charge, so it survives every request boundary.
// The "may" is offered only when BOTH halves can be done — a payment capacity of 1 (Credits / SEC_122
// Droids count) and a card in hand. Otherwise accepting could only spend a resource for nothing.
// The discount is a $oneShotPlayCharges row (GameLogic): "the next CARD" is any type — unit, event,
// upgrade — and the charge is spent on that card even when it costs less than 3.
function _SWUIc27145HandCards(int $player): array {
    global $playerID; $playerID = intval($player);
    $out = [];
    foreach (ZoneSearch('myHand', null) as $mz) {
        if (!SWUObjGone(GetZoneObject($mz))) $out[] = $mz;
    }
    return $out;
}

$onAttackAbilities["IC27_145:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    if (empty(_SWUIc27145HandCards(intval($player)))) return;
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1,
        tooltip: "Pay_1_and_discard_a_card_(the_next_card_you_play_this_phase_costs_3_less)?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', 'IC27_145#0', 1);
};

$customDQHandlers["IC27_145#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $hand = _SWUIc27145HandCards(intval($player));
    if (empty($hand)) return;
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;
    SWUQueueChooseTarget(intval($player), $hand, "Discard_a_card_from_your_hand", "IC27_145#1");
};

$customDQHandlers["IC27_145#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || strpos((string)$lastDecision, 'myHand-') !== 0) return;
    if (SWUObjGone(GetZoneObject($lastDecision))) return;
    if (DoDiscardCard(intval($player), $lastDecision) === '-') return;
    AddGlobalEffects(intval($player), 'SWU_IC27145_DISCOUNT_NEXT');
};
