<?php
// SEC_194
// Cost 1 - Fully Armed and Operational - [Cunning,Villainy]
// Text: If an opponent attacked your base during their previous action this phase, play a unit from your hand. Give it Ambush for this phase. / Plot

// SEC_194 Fully Armed and Operational — play the chosen hand unit at cost; it enters with Ambush this
// phase (the SEC_194 token doubles as a findable marker). Mirror of LOF_220#0: a nested play inside an
// event continuation doesn't resume the entered unit's entry triggers, so fire its When Played + the
// Ambush attack window manually.
$customDQHandlers["SEC_194#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID, $gTurnPlayer, $gPlayGrantTurnEffect; $playerID = intval($player);
    $playedCardID = '';
    $ho = GetZoneObject($lastDecision);
    if ($ho !== null) $playedCardID = $ho->CardID ?? '';
    $gPlayGrantTurnEffect = 'SEC_194';                 // gains Ambush this phase (registry) + findable marker
    SWUNestedPlay(intval($player), $lastDecision, false, 0);
    $gPlayGrantTurnEffect = null;
    // Locate the just-played unit by the SEC_194 marker.
    $playerID = intval($player);
    $mzPlayed = '';
    foreach (array_merge(ZoneSearch('myGroundArena', AnyUnitFilter), ZoneSearch('mySpaceArena', AnyUnitFilter)) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && is_array($o->TurnEffects ?? null) && in_array('SEC_194', $o->TurnEffects, true)) {
            $mzPlayed = $mz; break;
        }
    }
    if ($mzPlayed === '') return;
    if ($playedCardID !== '' && HasWhenPlayedAbility($playedCardID)) OnWhenPlayed(intval($player), $playedCardID, $mzPlayed);
    $playerID = intval($player);
    $o = GetZoneObject($mzPlayed);
    if (SWUObjGone($o)) return;
    $targets = SWUGetAllValidAmbushTargets(intval($player), $o, $o->Location ?? 'GroundArena'); // union all opponents
    if (empty($targets)) return;                       // no legal Ambush target → unit just enters with Ambush
    DecisionQueueController::AddDecision($player, "YESNO", "-", 1, tooltip: "Ambush_attack?");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SWUAmbushAnswer|{$mzPlayed}|" . implode('&', $targets), 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_194:0"] = function($player, $mzID = '') {
// Fully Armed and Operational — If an opponent attacked your base during their
                          // previous action this phase, play a unit from your hand. Give it Ambush this phase.
            global $playerID; $playerID = intval($player);
            // Read each OPPONENT'S OWN last action, not the global most-recent one: "during THEIR
            // previous action" is unaffected by anything we do in between (including the leader deploy
            // that opens the Plot window this card can be played from). Comma-delimited (see SWUAfterAction).
            // "IF AN opponent attacked your base" is an EXISTENTIAL CONDITION, not a target — nothing
            // downstream needs to know WHICH opponent (the effect only touches your own hand), so this
            // card must never prompt for a seat. OtherPlayer() asked exactly one seat, so above two seats
            // a base attack by seat 3 or 4 simply did not count.
            $cond = false;
            foreach (OpponentsOf(intval($player)) as $o) {
                $last = explode(',', GetSWUVar('SWU_LAST_ACTION_' . $o, ''));
                if (count($last) >= 2 && $last[0] === 'BASEATK' && intval($last[1]) === intval($player)) { $cond = true; break; }
            }
            if (!$cond) return;                                            // no opponent's previous action was a base attack
            $targets = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0);
            if (empty($targets)) return;                                   // nothing affordable to play
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_(it_gains_Ambush)", "SEC_194#0");
            return;
};
