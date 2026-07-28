<?php
// SHD_109
// Cost 14 - Endless Legions - [Command,Command]
// Text: Reveal any number of resources you control. Play each unit revealed this way for free (one at a time).

$customDQHandlers["SHD_109#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;   // passed → end loop
    global $playerID, $gTurnPlayer; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) { _SWUShd109OfferNext(intval($player)); return; }
    $savedTP = $gTurnPlayer; $savedPass = GetSWUVar('PASS', '0');
    ActivateCard(intval($player), $lastDecision, true, 0);   // reveal + play for free; its When Played fires
    $gTurnPlayer = $savedTP; SetSWUVar('PASS', $savedPass);
    $playerID = intval($player);
    _SWUShd109OfferNext(intval($player));                    // loop: offer the next unit-resource
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
