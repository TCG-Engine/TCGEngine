<?php
// LAW_041
// Cost 5 - Nothing Left to Fear - [Vigilance,Command]
// Text: Choose a friendly unit and give it +2/+2 for this phase. Then, you may defeat a non-leader unit with power equal to or less than the chosen unit.

// LAW_041 Nothing Left to Fear — step 0: buff the chosen friendly unit +2/+2 for this phase, then offer
// (you may) a non-leader unit with power <= the chosen unit's (now-buffed) power to defeat.
$customDQHandlers["LAW_041#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $chosen = GetZoneObject($lastDecision);
    if (SWUObjGone($chosen)) return;
    SWUApplyPhaseBuff($lastDecision, 2, 2, 'LAW_041');
    $chosen = GetZoneObject($lastDecision);            // re-read so the buff is reflected
    $chosenPower = ObjectCurrentPower($chosen);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
        'extraFilter' => fn($o) => ObjectCurrentPower($o) <= $chosenPower,
        'may' => true, 'question' => "Defeat_a_non-leader_unit?", 'prompt' => "Choose_a_non-leader_unit_to_defeat",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_041:0"] = function($player, $mzID = '') {
// Nothing Left to Fear — "Choose a friendly unit and give it +2/+2 for this
                          // phase. Then, you may defeat a non-leader unit with power equal to or less
                          // than the chosen unit." The buff is applied first (in the LAW_041#0
                          // continuation), so the power comparison uses the buffed power.
            global $playerID; $playerID = intval($player);
            $friendly = SWUFriendlyUnits();
            if (empty($friendly)) return;   // no friendly unit to buff → fizzle
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit", "LAW_041#0");
            return;
};
