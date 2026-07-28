<?php
// SOR_235
// Cost 7 - Galactic Ambition - [Villainy]
// Text: Play a non-[Heroism] unit from your hand for free. Deal damage to your base equal to its cost.

// SOR_235 Galactic Ambition — play the chosen non-Heroism hand unit for free, then deal its PRINTED
// cost to your own base. Capture the cost before playing (the card leaves hand). The turn-state guard
// mirrors SWUPlayTopDeckCard so the nested ActivateCard doesn't double-advance the turn.
$customDQHandlers["SOR_235#0"] = function($player, $parts, $lastDecision) {
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cost      = intval(CardCost($o->CardID));
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, true);  // free play
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
    SWUDealDamageToBase($cost, intval($player));          // damage to YOUR base = its cost
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_235:0"] = function($player, $mzID = '') {
// Galactic Ambition — "Play a non-[Heroism] unit from your hand for free.
                          // Deal damage to your base equal to its cost."
            global $playerID;
            $playerID = intval($player);
            $targets = [];
            foreach (ZoneSearch("myHand", NonLeaderUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (stripos(CardAspect($o->CardID) ?? '', 'Heroism') !== false) continue; // non-Heroism only
                $targets[] = $mz;
            }
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_non-Heroism_unit_from_your_hand_for_free", "SOR_235#0");
            return;
};
