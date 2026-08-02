<?php
// JTL_096
// Cost 3 - Blue Leader - Scarif Air Support - [Command,Heroism] - Power 3 - HP 3
// Text: Ambush (When you play this unit, it may attack an enemy unit.) / When Played: You may pay 2 resources. If you do, move this unit to the ground arena and give 2 Experience tokens to it. (It's a ground unit.)

// JTL_096 Blue Leader — Ambush (keyword) + When Played: You may pay 2 resources. If you do, move this
// unit to the ground arena and give it 2 Experience tokens. (It becomes a ground unit.)
$whenPlayedAbilities["JTL_096:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($mzID);
    if (SWUObjGone($o)) return;
    // Gate the OFFER on total payment capacity, not on ready resources alone: a Credit token may be
    // defeated to pay 1 resource of any cost (CR 3.13) and a SEC_122 Droid may be exhausted, so a player
    // holding 1 ready resource + 1 Credit CAN pay the 2 and must be offered the move.
    if (SWUTotalPaymentCapacity(intval($player)) < 2) return; // can't pay → no offer
    $uid = intval($o->UniqueID ?? 0);
    DecisionQueueController::AddDecision($player, 'YESNO', '-', 1,
        tooltip: "Pay_2_to_move_Blue_Leader_to_the_ground_arena_with_2_Experience?");
    DecisionQueueController::AddDecision($player, 'CUSTOM', 'JTL_096#0|' . $uid, 1);
};

$customDQHandlers["JTL_096#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID;
    $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 2) return;
    $uid = intval($parts[0] ?? 0);
    if (SWUFindMzByUID($uid) === null) return;
    // Route the payment through the shared alt-payment funnel (Credit tokens, then SEC_122 Droids, then
    // resources for the remainder). The JTL_096_MOVE_PAY continuation pays what's left and performs the
    // move + 2 Experience, so the whole cost is honored no matter which mix the player uses.
    SWUOfferAltPayment(intval($player), 2, 'JTL_096_MOVE_PAY', strval($uid), 1);
};
