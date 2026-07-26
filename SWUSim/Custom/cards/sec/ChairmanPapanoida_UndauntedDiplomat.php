<?php
// SEC_159
// Cost 3 - Chairman Papanoida - Undaunted Diplomat - [Aggression,Aggression] - Power 2 - HP 6
// Text: When a player draws 1 or more cards during the action phase: You may disclose AggressionAggression (reveal cards from your hand with these aspect icons among them). If you do, create a Spy token.

// SEC_159 Chairman Papanoida — When a player draws 1+ cards during the action phase: its controller
// may disclose AggressionAggression → create a Spy token. Wired in _SWUOnPlayerDrew (below).
$customDQHandlers["SEC_159#0"] = function($player, $parts, $lastDecision) {
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};
