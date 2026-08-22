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
                // Which opponent's pile this event was played FROM is not knowable from OtherPlayer()
                // above two seats — search every live opponent and name the seat that actually holds it.
                foreach (OpponentsOf(intval($player)) as $opp) {
                    $od = GetDiscard($opp);
                    for ($i = 0; $i < count($od); $i++) {
                        if (!empty($od[$i]->removed)) continue;
                        if (($od[$i]->CardID ?? '') === 'SOR_126') { $mz = SWUForeignMzID(intval($player), $opp, 'Discard', $i); break 2; }
                    }
                }
            }
            if ($mz !== null) SWURampResourceExhausted(intval($player), $mz); // enters exhausted (no "ready" wording)
            return;
};
