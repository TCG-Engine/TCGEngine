<?php
// LAW_257
// Cost 1 - Hidden Hand Supplier - Power 1 - HP 2
// Text: When Played: You may pay 1 resource. If you do, give an Experience token to another unit.

// LAW_257 Hidden Hand Supplier — When Played: you may pay 1 resource. If you do, give an Experience
// token to another unit.
$whenPlayedAbilities["LAW_257:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUTotalPaymentCapacity(intval($player)) < 1) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    // An optional COST whose effect could only fizzle is not offered — with the Supplier as the only unit
    // in play there is no "another unit" to receive the token, so prompting could only burn a resource for
    // nothing. Guarded by LoneUnit_NoOtherTargetExists_NoPayOffer.
    $hasOther = false;
    foreach (SWUAllUnits(null) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $uid) { $hasOther = true; break; }
    }
    if (!$hasOther) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_give_an_Experience_token_to_another_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_257#0|{$uid}", 1);
};

$customDQHandlers["LAW_257#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUPayInlineAbilityCost(intval($player), 1)) return;
    $uid = intval($parts[0] ?? 0);
    // Give an Experience token to ANOTHER unit (either player; exclude self by UID).
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'GIVE_EXPERIENCE', 'side' => 'any', 'excludeUID' => $uid,
        'prompt' => "Give_an_Experience_token_to_another_unit",
    ]);
};
