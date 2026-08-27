<?php
// SEC_085
// Cost 4 - Vice Admiral Rampart - On Schedule - [Command,Villainy] - Power 3 - HP 6
// Text: On Attack: You may disclose CommandCommandVillainy (reveal cards from your hand with these aspect icons among them). If you do, give an Experience token to each of up to 2 other units.

// SEC_085 Vice Admiral Rampart — On Attack: you may disclose CommandCommandVillainy → give an
// Experience token to each of up to 2 OTHER units.
$onAttackAbilities["SEC_085:0"] = function($player, $mzID) {
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    SWUQueueDisclose(intval($player), ['Command', 'Command', 'Villainy'], "SEC_085#0|{$selfUID}",
        "Disclose_CommandCommandVillainy_to_give_Experience");
};

$customDQHandlers["SEC_085#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $selfUID = intval($parts[0] ?? 0);
    $others = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $selfUID) continue;   // "other units"
        $others[] = $mz;
    }
    if (empty($others)) return;
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE",
        "0|2|" . implode('&', $others), 1, tooltip: "Give_an_Experience_token_to_up_to_2_other_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_085#1", 1, dontSkipOnPass: 1);
};

$customDQHandlers["SEC_085#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $picks = array_slice(array_values(array_filter(explode('&', (string)$lastDecision),
        fn($m) => $m !== '' && $m !== '-')), 0, 2);   // cap at 2 (validate the answer)
    foreach ($picks as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        DoGiveExperienceToken(intval($player), $mz);
    }
};
