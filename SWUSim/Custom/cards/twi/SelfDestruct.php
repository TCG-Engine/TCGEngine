<?php
// TWI_140
// Cost 2 - Self-Destruct - [Aggression,Villainy]
// Text: Defeat a friendly unit. If you do, deal 4 damage to a unit.

// TWI_140 Self-Destruct (event continuation) — friendly unit chosen ($lastDecision); defeat it, then
// deal 4 damage to any unit.
$customDQHandlers["TWI_140#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWUDefeatUnit(intval($player), $lastDecision);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 4,
        'prompt' => "Deal_4_damage_to_a_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_140:0"] = function($player, $mzID = '') {
// Self-Destruct — "Defeat a friendly unit. If you do, deal 4 damage to a unit."
            global $playerID; $playerID = intval($player);
            $friendly = SWUFriendlyUnits(null, NonLeaderUnitFilter);
            if (empty($friendly)) return; // no friendly unit → no defeat, no damage
            SWUQueueChooseTarget(intval($player), $friendly, "Defeat_a_friendly_unit", "TWI_140#0");
            return;
};
