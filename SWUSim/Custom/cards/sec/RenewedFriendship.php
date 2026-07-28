<?php
// SEC_105
// Cost 4 - Renewed Friendship - [Command,Heroism]
// Text: Return a unit from your discard pile to your hand. / Create 2 Spy tokens.

// SEC_105 Renewed Friendship — return a unit from discard to hand, then create 2 Spy tokens.
$customDQHandlers["SEC_105#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') {
        SWUReturnFromDiscardToHand(intval($player), $lastDecision);
    }
    SWUCreateUnitTokens(intval($player), 'SEC_T01', 2);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_105:0"] = function($player, $mzID = '') {
// Renewed Friendship — "Return a unit from your discard pile to your hand.
                          // Create 2 Spy tokens."
            global $playerID; $playerID = intval($player);
            $units = ZoneSearch("myDiscard", AnyUnitFilter);
            if (empty($units)) { SWUCreateUnitToken(intval($player), 'SEC_T01'); SWUCreateUnitToken(intval($player), 'SEC_T01'); return; }
            SWUQueueChooseTarget(intval($player), $units, "Return_a_unit_from_your_discard_to_hand", "SEC_105#0");
            return;
};
