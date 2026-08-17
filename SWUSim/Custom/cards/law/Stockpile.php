<?php
// LAW_171
// Stockpile
// Text: Resource this event and the top card of your deck.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_171:0"] = function($player, $mzID = '') {
// Stockpile — "Resource this event and the top card of your deck." Both enter
                          // the resource zone exhausted (plain "resource", not "ready").
            global $playerID; $playerID = intval($player);
            // "Resource THIS EVENT" — the spent event goes to its OWNER's discard, which is not the
            // caster's pile when the event is played from a foreign zone (LAW_215 Vermillion's free
            // play of an opponent-owned card). The old lookup scanned the CASTER's discard and simply
            // no-opped, silently dropping half the card and stranding the event in the owner's pile.
            // `_SWUFindDiscardMzID` returns a frame-relative "myDiscard-N" token for the seat it
            // scanned, so for owner != caster it must be re-framed to "theirDiscard-N" before MZMove
            // (which runs in the caster's frame) — the cross-seat relative-token family, already
            // solved this way in law/DisplayPiece.php and law/ExpendableMercenary.php.
            // Owner is PRESERVED, not rewritten to the caster: resourcing a card does not transfer
            // ownership, and overwriting it would erase the split the moment the lookup started working.
            // Look in the caster's pile first (the normal from-hand play), then the opponent's. The
            // event's own $mzID is not usable here: by the time this runs the card has already left
            // play for a discard, so GetZoneObject($mzID) is null/stale and cannot tell us the owner.
            $me    = intval($player);
            $owner = $me;
            $evMz  = _SWUFindDiscardMzID($me, 'LAW_171');
            if ($evMz === null) {
                $opp   = OtherPlayer($me);
                $evMz  = _SWUFindDiscardMzID($opp, 'LAW_171');
                if ($evMz !== null) $owner = $opp;
            }
            if ($evMz !== null) {
                $playerID = intval($player);
                if ($owner !== intval($player)) $evMz = str_replace('myDiscard', 'theirDiscard', $evMz);
                $r = MZMove(intval($player), $evMz, "myResources");
                if ($r !== null) { $r->Status = 0; $r->Owner = $owner; $r->Controller = intval($player); }
            }
            // Top card of the deck → resources (exhausted).
            // CleanupRemovedCards first: when Stockpile is itself played OFF the deck (a free-play
            // reveal), the deck still carries the removed entry for this very event, so ZoneSearch's
            // index 0 is a stale token and the MZMove silently no-ops — the deck-top was never
            // resourced. Same stale-token shape as the SHD_116 / SHD_125 deck reads.
            DecisionQueueController::CleanupRemovedCards();
            $playerID = intval($player);
            $deck = ZoneSearch("myDeck", null);
            if (!empty($deck)) {
                $r2 = MZMove(intval($player), $deck[0], "myResources");
                if ($r2 !== null) { $r2->Status = 0; $r2->Owner = intval($player); $r2->Controller = intval($player); }
            }
            SWUKeepCreditTokensLast(intval($player));
            return;
};
