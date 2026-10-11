<?php
// HMW_248 Defoliator Tank — Unit (Ground) 4/6, cost 5.
// "Overwhelm. On Attack: If the defending unit isn't a Droid or Vehicle, you may pay 2 resources. If you do,
//  give 2 Weakness tokens to it."
// Overwhelm is keyword-wired. A base defender is not a unit (no offer). The cost is an ordinary "you may pay
// N": offered only with payment capacity for 2 (Credits included), paid through SWUOfferAltPayment, whose
// HMW_248_PAY continuation (GameLogic) gives the tokens to the defender by UniqueID.

$onAttackAbilities["HMW_248:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $defMz = (string)GetSWUVar('SWU_CURRENT_DEFENDER');
    if ($defMz === '' || strpos($defMz, 'Arena') === false) return;
    $def = GetZoneObject($defMz);
    if (SWUObjGone($def) || TraitContains($def, 'Droid') || TraitContains($def, 'Vehicle')) return;
    if (SWUTotalPaymentCapacity(intval($player)) < 2) return;
    $uid = intval($def->UniqueID ?? 0);
    DecisionQueueController::AddDecision(intval($player), 'YESNO', '-', 1, tooltip: "Pay_2_to_give_2_Weakness_tokens_to_the_defender?");
    DecisionQueueController::AddDecision(intval($player), 'CUSTOM', "HMW_248#0|{$uid}", 1);
};

$customDQHandlers["HMW_248#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 2) return;
    $uid = intval($parts[0] ?? 0);
    if (SWUFindMzByUID($uid) === null) return;
    SWUOfferAltPayment(intval($player), 2, 'HMW_248_PAY', strval($uid), 1);
};
