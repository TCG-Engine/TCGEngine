<?php
// SOR_219
// Cost 2 - Sneak Attack - [Cunning]
// Text: Play a unit from your hand. It costs [3 resources] less and enters play ready. At the start of the regroup phase, defeat it.

// SOR_219 Sneak Attack — plays the chosen hand unit ($lastDecision) at a 3-resource discount, forcing
// it to enter READY ($gForceEnterReady) and tagging it SWU_SNEAK_DEFEAT (RegroupPhaseStart defeats it).
// This runs inside the EVENT's resolution, so the event's FINISH_PLAY_CARD owns the after-action: the
// inner ActivateCard's own turn advance is neutralised by capturing/restoring the turn state (mirrors
// SWUPlayTopDeckCard). Mandatory single-target choose, so a '-'/empty answer means no playable unit.
$customDQHandlers["SOR_219#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gForceEnterReady, $gPlayGrantTurnEffect, $gTurnPlayer;
    $playerID = intval($player);
    $savedTP   = $gTurnPlayer;
    $savedPass = GetSWUVar('PASS', '0');
    $gForceEnterReady     = true;
    $gPlayGrantTurnEffect = 'SWU_SNEAK_DEFEAT';
    ActivateCard(intval($player), $lastDecision, false, 3);
    $gForceEnterReady     = false;
    $gPlayGrantTurnEffect = null;
    $gTurnPlayer = $savedTP;
    SetSWUVar('PASS', $savedPass);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_219:0"] = function($player, $mzID = '') {
// Sneak Attack — "Play a unit from your hand. It costs 3 less and enters
                          // play ready. At the start of the regroup phase, defeat it." Offer the hand
                          // units the player can afford at the -3 discount; SOR_219 plays the pick.
            global $playerID;
            $playerID = intval($player);
            $targets = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 3);
            if (empty($targets)) return; // no affordable unit → fizzle (event already in discard)
            SWUQueueChooseTarget(intval($player), $targets,
                "Play_a_unit_(costs_3_less,_enters_ready,_defeated_at_regroup)", "SOR_219#0");
            return;
};
