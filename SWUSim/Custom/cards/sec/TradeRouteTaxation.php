<?php
// SEC_126
// Cost 2 - Trade Route Taxation - [Command]
// Text: Choose an opponent. If you control more units than that opponent, they can't play events for this phase. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_126 Trade Route Taxation — if you control more units than the CHOSEN opponent, lock their events.
$customDQHandlers["SEC_126#OPP"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    $mine = 0; foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $mine++; }
    $theirs = 0; foreach (GetUnitsInPlay($opp) as $u) { if (empty($u->removed)) $theirs++; }
    if ($mine > $theirs) AddGlobalEffects($opp, 'SWU_EVENT_LOCK');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_126:0"] = function($player, $mzID = '') {
// Trade Route Taxation — choose an opponent; if you control more units than
                          // that opponent, they can't play events for this phase.
            global $playerID; $playerID = intval($player);
            if (SeatCountForGame() > 2) {   // Twin Suns: pick which opponent to tax
                SWUQueueChooseOpponent(intval($player), "SEC_126#OPP", "Lock_events_for_which_opponent?");
                return;
            }
            $opp = OtherPlayer(intval($player));
            $mine = 0; foreach (GetUnitsInPlay(intval($player)) as $u) { if (empty($u->removed)) $mine++; }
            $theirs = 0; foreach (GetUnitsInPlay($opp) as $u) { if (empty($u->removed)) $theirs++; }
            if ($mine > $theirs) AddGlobalEffects($opp, 'SWU_EVENT_LOCK');
            return;
};
