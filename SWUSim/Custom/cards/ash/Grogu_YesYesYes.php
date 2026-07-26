<?php
// ASH_155
// Cost 3 - Grogu - Yes. Yes. Yes. - [Aggression,Heroism] - Power 2 - HP 6
// Text: When you take the initiative: You may attack with a unit.

// ASH_155 Grogu — "When you take the initiative: you may attack with a unit." Bonus attack from the
// take-initiative window. On accept, suppress the bonus attack's terminal after-action (the initiative
// pass already swapped the turn — see SWUAfterAction's SWU_SUPPRESS_AFTERACTION gate) and attack with
// the chosen unit. Decline → nothing.
$customDQHandlers["ASH_155#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    SetSWUVar('SWU_SUPPRESS_AFTERACTION', '1');
    BeginSWUAttack(intval($player), $lastDecision);
};
