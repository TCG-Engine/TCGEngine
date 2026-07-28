<?php
// SEC_107
// Cost 5 - Chancellor Valorum - Civil Servant - [Command,Command] - Power 3 - HP 7
// Text: When this unit completes an attack: You may disclose CommandCommandCommand (reveal cards from your hand with these aspect icons among them). If you do, put the top card of your deck into play as a resource.

// SEC_107 Chancellor Valorum — When this unit completes an attack: you may disclose CommandCommandCommand
// → put the top card of your deck into play as a resource (enters exhausted — no "ready" wording).
$onAttackEndAbilities["SEC_107:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Command', 'Command', 'Command'], "SEC_107#0",
        "Disclose_CommandCommandCommand_to_ramp_a_resource");
};

$customDQHandlers["SEC_107#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $deck = ZoneSearch("myDeck", null);
    if (empty($deck)) return;
    SWURampResourceExhausted(intval($player), $deck[0]);
};
