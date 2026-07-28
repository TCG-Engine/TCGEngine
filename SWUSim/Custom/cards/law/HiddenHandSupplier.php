<?php
// LAW_257
// Cost 1 - Hidden Hand Supplier - Power 1 - HP 2
// Text: When Played: You may pay 1 resource. If you do, give an Experience token to another unit.

// LAW_257 Hidden Hand Supplier — When Played: you may pay 1 resource. If you do, give an Experience
// token to another unit.
$whenPlayedAbilities["LAW_257:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (SWUResourceCount(intval($player), readyOnly: true) < 1) return;
    $self = GetZoneObject($mzID);
    $uid  = SWUObjUID($self, 0);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Pay_1_resource_to_give_an_Experience_token_to_another_unit?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_257#0|{$uid}", 1);
};

$customDQHandlers["LAW_257#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (!SWUExhaustResources(intval($player), 1)) return;
    $uid = intval($parts[0] ?? 0);
    // Give an Experience token to ANOTHER unit (either player; exclude self by UID).
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'GIVE_EXPERIENCE', 'side' => 'any', 'excludeUID' => $uid,
        'prompt' => "Give_an_Experience_token_to_another_unit",
    ]);
};
