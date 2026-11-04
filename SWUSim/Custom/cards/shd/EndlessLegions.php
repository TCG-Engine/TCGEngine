<?php
// SHD_109
// Cost 14 - Endless Legions - [Command,Command]
// Text: Reveal any number of resources you control. Play each unit revealed this way for free (one at a time).

// The "then" step of each free play: marks it as this event's, so a deferred play resumes nested (see SHD_109#0).
if (!function_exists('_SWUShd109PlayDone')) {
    function _SWUShd109PlayDone(int $player, string $placedMz): void {}
}

$customDQHandlers["SHD_109#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;   // passed → end loop
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { _SWUShd109OfferNext(intval($player)); return; }
    // Reveal + play for free, IN FULL (bug report P-19). SWUNestedPlay entered at ActivateCard, the second half of a
    // play, so everything SWUBeginPlayCard owns before payment was skipped — TWI_116 Clone's copy choice (it entered as
    // a 0/0 and died), Exploit, every additional cost. "Play for free ignores all resource costs, including the aspect
    // penalty, but not other additional costs" (official ruling): free = a discount of the whole play cost, aspect
    // penalty included, through the full ceremony. The no-op "then" step makes a play that stops for a choice (the
    // copy pick) resume nested at the PLAY_CARD dispatch, so its own close is refused there too — this event's play
    // ceremony is what ends the action.
    SWUNestedPlayUnit(intval($player), $lastDecision, SWUComputePlayCost(intval($player), $o), ['then' => '_SWUShd109PlayDone']);
    $playerID = intval($player);
    // Loop: offer the next unit-resource — but only AFTER this unit's triggered abilities have fully
    // resolved. Official ruling (07/16/2024): "If playing multiple units, resolve all abilities triggered
    // while playing each unit before playing the next unit." Re-offering inline queued the next pick at
    // block 1, the same block the played unit's own decisions use, and AddDecision inserts after every
    // equal block, so an interactive When Played drained BEHIND the next play (bug report #1110: nine units
    // played before any of their When Playeds resolved, and Thrawn's then readied the wrong unit).
    // Block 4 = after every trigger chain (RESOLVE_* at depth 1-3) yet still inside the event's own effect:
    // TWI_210's "when a card is played" collector waits at block 5 and FINISH_PLAY_CARD at 10. Same shape
    // as HMW_008 Grievous's re-offer. dontSkipOnPass: a "may" declined inside the played unit's When Played
    // leaves a sticky PASS, and an unflagged CUSTOM behind it would silently end the loop.
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SHD_109#1|" . intval($player), 4, '', 1);
};

// The next offer. ⚠ The played unit's When Played may have asked ANOTHER seat (SEC_193 Thrawn: "an opponent
// may choose a unit…"), and that decision sits on THAT seat's queue — the caster's own queue drains past it,
// so without this the next unit could be played before the opponent answers. While some other seat owes a
// decision, hop onto its queue and offer from there once it has answered (FINISH_PLAY_CARD's hop).
$customDQHandlers["SHD_109#1"] = function($player, $parts, $lastDecision) {
    $caster = intval($parts[0] ?? $player);
    $owing  = _SWUSeatOwingCrossPlayerDecision($caster, intval($player), true);
    if ($owing > 0) {
        DecisionQueueController::AddDecision($owing, "CUSTOM", "SHD_109#1|{$caster}", 4, '', 1);
        return;
    }
    _SWUShd109OfferNext($caster);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_109:0"] = function($player, $mzID = '') {
// Endless Legions — "Reveal any number of resources you control. Play each unit
                          // revealed this way for free (one at a time)." Iterative reveal-one loop: offer the
                          // player's UNIT resources (MZMAYCHOOSE; non-unit resources aren't offered), free-play
                          // the pick, re-offer; a pass (or no units left) ends the loop.
            global $playerID; $playerID = intval($player);
            _SWUShd109OfferNext(intval($player));
            return;
};
