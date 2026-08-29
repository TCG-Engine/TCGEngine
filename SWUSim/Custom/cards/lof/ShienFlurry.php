<?php
// LOF_220
// Cost 1 - Shien Flurry - [Cunning]
// Text: Play a Force unit from your hand (paying its cost). It gains Ambush for this phase. The next time it would be dealt damage this phase, prevent 2 of that damage.

// LOF_220 Shien Flurry — play the chosen Force unit; it enters with Ambush (this phase) and a one-shot
// "prevent 2 of the next damage" marker (both applied at entry, before the Ambush attack resolves).
$customDQHandlers["LOF_220#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect, $gPlayGrantPrevent2;
    $playerID = intval($player);
    // CardID of the unit being played (so we can fire its When Played ourselves — see below).
    $playedCardID = '';
    $ho = GetZoneObject($lastDecision);
    if ($ho !== null) $playedCardID = $ho->CardID ?? '';
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    $gPlayGrantTurnEffect = 'AMBUSH';   // keyword present this phase
    $gPlayGrantPrevent2   = true;
    // NESTED FRAME (2026-08-29) — see Osha: the inner after-action must not consume this action's close.
    SWUWithNestedActionFrame(fn() => ActivateCard(intval($player), $lastDecision, false, 0));
    $gPlayGrantTurnEffect = null;
    $gPlayGrantPrevent2   = null;
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    // ActivateCard collected + flushed the entered unit's entry triggers (When Played + granted Ambush);
    // do NOT re-fire them here (that double-shielded / double-attacked).
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_220:0"] = function($player, $mzID = '') {
// Shien Flurry — "Play a Force unit from your hand (paying its cost). It gains
                          // Ambush for this phase. The next time it would be dealt damage this phase,
                          // prevent 2 of that damage."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed) && HasTrait($o->CardID ?? '', 'Force')) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_Force_unit_(Ambush_+_prevent_2_of_next_damage)", "LOF_220#0");
            return;
};
