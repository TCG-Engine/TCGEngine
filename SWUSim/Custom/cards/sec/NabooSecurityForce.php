<?php
// SEC_120
// Cost 6 - Naboo Security Force - [Command] - Power 5 - HP 7
// Text: When Played/When Defeated: You may disclose Command (reveal a card from your hand with this aspect icon). If you do, give a friendly unit Sentinel for this phase.

$customDQHandlers["SEC_120#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $friendly = SWUAllUnits('my');
    if (empty($friendly)) return;
    SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_Sentinel_this_phase",
        "GRANT_PHASE_KEYWORD|SENTINEL^SEC_120");
};

// SEC_120 Naboo Security Force — When Played / When Defeated: you may disclose Command → give a
// friendly unit Sentinel for this phase.
$sec120Disclose = function ($player, $mzID) {
  SWUQueueDisclose(
    intval($player),
    ['Command'],
    "SEC_120#0",
    "Disclose_Command_to_give_a_friendly_unit_Sentinel"
  );
};

$whenPlayedAbilities["SEC_120:0"] = $sec120Disclose;

$whenDefeatedAbilities["SEC_120:0"] = $sec120Disclose;
