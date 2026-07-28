<?php
// TWI_246
// Cost 7 - Tranquility - Inspiring Flagship - [Heroism] - Power 7 - HP 6
// Text: When Played: You may return a Republic unit from your discard pile to your hand. / On Attack: Each of the next 3 Republic cards you play this phase costs 1 resource less.

// TWI_246 Tranquility — "When Played: You may return a Republic unit from your discard pile to your hand.
// On Attack: Each of the next 3 Republic cards you play this phase costs 1 resource less."
$whenPlayedAbilities["TWI_246:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = [];
    foreach (ZoneSearch("myDiscard", ['Unit', 'Token Unit']) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Republic')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_return_a_Republic_unit_from_discard_to_hand", "Return_a_Republic_unit", "TWI_246#0");
};

$customDQHandlers["TWI_246#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};

$onAttackAbilities["TWI_246:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // Arm "each of the next 3 Republic cards costs 1 less this phase" (count-based flag; consumed per
    // Republic play in ActivateCard, cleared at RegroupPhaseStart).
    for ($i = 0; $i < 3; $i++) AddGlobalEffects(intval($player), 'SWU_TWI246_DISCOUNT');
    // Combat owns the after-action.
};
