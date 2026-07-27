<?php
// SHD_154
// Cost 6 - Wrecker - Boom! - [Heroism,Aggression] - Power 7 - HP 6
// Text: Overwhelm / When Played: You may defeat a friendly resource. If you do, deal 5 damage to a ground unit.

// ─── SHD_154 Wrecker ──────────────────────────────────────────────────────────
// Overwhelm (auto) + When Played: You may defeat a friendly resource. If you do, deal 5 damage to a
// ground unit. Player picks WHICH resource (MZMAYCHOOSE over myResources — mirrors SHD_107).
$whenPlayedAbilities["SHD_154:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $res = ZoneSearch('myResources');
    SWUQueueMayChooseTarget(intval($player), $res,
        "Defeat_a_friendly_resource_to_deal_5_to_a_ground_unit?",
        "Defeat_a_friendly_resource", "SHD_154#0");
};

$customDQHandlers["SHD_154#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!SWUDefeatResource(intval($player), $lastDecision)) return;
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 5, 'side' => 'any', 'arena' => 'Ground',
        'prompt' => "Deal_5_to_a_ground_unit",
    ]);
};
