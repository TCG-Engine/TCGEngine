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
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    $gPlayGrantTurnEffect = 'SEC_194';                 // gains Ambush this phase (registry) + findable marker
    ActivateCard(intval($player), $lastDecision, false, 0);
    $gPlayGrantTurnEffect = null;
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
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
            $opp  = OtherPlayer(intval($player));
            // Read the OPPONENT'S OWN last action, not the global most-recent one: "during THEIR previous
            // action" is unaffected by anything we do in between (including the leader deploy that opens
            // the Plot window this card can be played from). Comma-delimited (see SWUAfterAction).
            $last = explode(',', GetSWUVar('SWU_LAST_ACTION_' . $opp, ''));
            $cond = (count($last) >= 2 && $last[0] === 'BASEATK' && intval($last[1]) === intval($player));
            if (!$cond) return;                                            // opponent's previous action wasn't a base attack
            $targets = SWUHandPlayablesAtDiscount(intval($player), ['Unit'], 0);
            if (empty($targets)) return;                                   // nothing affordable to play
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_(it_gains_Ambush)", "SEC_194#0");
            return;
};
