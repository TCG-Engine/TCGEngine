<?php
// LAW_227
// Cost 1 - Rookie Rocket-jumper - [Cunning] - Power 2 - HP 1
// Text: When Played: You may pay 1 resource. If you do, give a Shield token to this unit.

// LAW_227 Rookie Rocket-jumper — When Played: you may pay 1 resource. If you do, give a Shield token to
// this unit.
$whenPlayedAbilities["LAW_227:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_give_this_unit_a_Shield?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_227#0|{$uid}", 1);
};

$customDQHandlers["LAW_227#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz !== null) DoGiveShieldToken(intval($player), $mz);
};
