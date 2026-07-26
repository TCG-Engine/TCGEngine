<?php
// SOR_126
// Resupply
// Text: Put this event into play as a resource.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_126:0"] = function($player, $mzID = '') {
// Resupply — "Put this event into play as a resource."
            global $playerID;
            $playerID = intval($player);
            $mz = _SWUFindDiscardMzID(intval($player), 'SOR_126'); // own discard (normal play)
            if ($mz === null) {
                // Played from the OPPONENT's discard (SEC_205 Obi-Wan): "this event" sits in their discard —
                // it still becomes a resource under the CASTER (the player who played it).
                $opp = OtherPlayer(intval($player));
                $od  = GetDiscard($opp);
                for ($i = 0; $i < count($od); $i++) {
                    if (!empty($od[$i]->removed)) continue;
                    if (($od[$i]->CardID ?? '') === 'SOR_126') { $mz = "theirDiscard-{$i}"; break; }
                }
            }
            if ($mz !== null) SWURampResourceExhausted(intval($player), $mz); // enters exhausted (no "ready" wording)
            return;
};
