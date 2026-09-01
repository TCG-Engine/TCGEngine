<?php
// JTL_130
// Cost 5 - Timely Reinforcements - [Command]
// Text: Choose an opponent. For every 2 resources they control, create an X-Wing token and give it Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.)

// JTL_130 Timely Reinforcements — X-Wing (Sentinel) per 2 resources the CHOSEN opponent controls.
$customDQHandlers["JTL_130#OPP"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $n = intdiv(SWUResourceCount(intval($opp)), 2);   // CR 3.13: Credit tokens are NOT resources
    SWUCreateUnitTokens(intval($player), 'JTL_T02', $n, false, 'JTL_130');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_130:0"] = function($player, $mzID = '') {
// Timely Reinforcements — choose an opponent; for every 2 resources they
                          // control, create an X-Wing token and give it Sentinel for this phase.
            global $playerID;
            $playerID = intval($player);
            if (SeatCountForGame() > 2) {   // Twin Suns: pick which opponent's resources to count
                SWUQueueChooseOpponent(intval($player), "JTL_130#OPP", "Count_which_opponent's_resources?");
                return;
            }
            $opp = GetOpponent(intval($player));
            $n = intdiv(SWUResourceCount(intval($opp)), 2);   // CR 3.13: Credit tokens are NOT resources
            // X-Wing (Space, 2/2) with JTL_130 (Sentinel this phase); the marker rides the batch funnel so
            // any Moff-Jerjerrod-doubled X-Wings get it too.
            SWUCreateUnitTokens(intval($player), 'JTL_T02', $n, false, 'JTL_130');
            return;
};
