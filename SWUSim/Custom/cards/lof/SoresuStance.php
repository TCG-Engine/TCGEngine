<?php
// LOF_076
// Cost 1 - Soresu Stance - [Vigilance]
// Text: Play a Force unit from your hand (paying its cost) and give a Shield token to it.

// LOF_076 Soresu Stance — play the chosen Force unit from hand at full cost and give it a Shield token
// (via the $gPlayGrantShield entry hook). Neutralise the inner ActivateCard's turn advance (the event's
// FINISH_PLAY_CARD owns the after-action), mirroring LOF_123#0.
$customDQHandlers["LOF_076#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer, $gPlayGrantShield;
    $playerID  = intval($player);
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    $gPlayGrantShield = 1;
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gPlayGrantShield = null;
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_076:0"] = function($player, $mzID = '') {
// Soresu Stance — "Play a Force unit from your hand (paying its cost) and give a
                          // Shield token to it."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_Force_unit_from_your_hand_(gives_it_a_Shield)", "LOF_076#0");
            return;
};
