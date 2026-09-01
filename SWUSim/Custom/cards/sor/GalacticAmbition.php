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
    SWUNestedPlay(intval($player), $lastDecision, true, 0);  // free play
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
            // ⚠ PLAY-FROM-HAND IS ALWAYS DECLINABLE (standing ruling): the hand is a HIDDEN zone, so a
            // player can never be forced to reveal they were holding a playable card. That holds even
            // though this card prints no "you may". MZMAYCHOOSE, not MZCHOOSE.
            // It is MATERIAL here, not cosmetic — the rider deals damage to YOUR OWN base equal to the
            // unit's cost. Worse, a mandatory choose with exactly ONE legal unit in hand auto-resolves
            // to PASSPARAMETER and raises no prompt at all, so the player was silently forced to play
            // it and eat the damage. The continuation already handles the decline
            // (`if (SWUDecisionDeclined(...)) return;`) — only the decision TYPE was wrong.
            SWUQueueMayChooseTarget(intval($player), $targets,
                "Play_a_non-Heroism_unit_from_your_hand_for_free?",
                "Play_a_non-Heroism_unit_from_your_hand_for_free", "SOR_235#0");
            return;
};
