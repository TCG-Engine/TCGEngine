<?php
// LAW_171
// Stockpile
// Text: Resource this event and the top card of your deck.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_171:0"] = function($player, $mzID = '') {
// Stockpile — "Resource this event and the top card of your deck." Both enter
                          // the resource zone exhausted (plain "resource", not "ready").
            global $playerID; $playerID = intval($player);
            // The event is already in the caster's discard (moved before OnPlayEvent) — move it to resources.
            $evMz = _SWUFindDiscardMzID(intval($player), 'LAW_171');
            if ($evMz !== null) {
                $r = MZMove(intval($player), $evMz, "myResources");
                if ($r !== null) { $r->Status = 0; $r->Owner = intval($player); $r->Controller = intval($player); }
            }
            // Top card of the deck → resources (exhausted).
            $deck = ZoneSearch("myDeck", null);
            if (!empty($deck)) {
                $r2 = MZMove(intval($player), $deck[0], "myResources");
                if ($r2 !== null) { $r2->Status = 0; $r2->Owner = intval($player); $r2->Controller = intval($player); }
            }
            SWUKeepCreditTokensLast(intval($player));
            return;
};
