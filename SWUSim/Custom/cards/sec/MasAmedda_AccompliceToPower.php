<?php
// SEC_084
// Cost 4 - Mas Amedda - Accomplice to Power - [Command,Villainy] - Power 3 - HP 4
// Text: When Played: Give an Experience token to each of up to 2 other Official units. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_084 Mas Amedda — When Played: give an Experience token to each of up to 2 OTHER Official units. (Plot auto.)
$whenPlayedAbilities["SEC_084:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $officials = [];
    // ⚠ TWO DEFECTS FIXED ON THIS LOOP, the same pair as SOR_007 Grand Moff Tarkin's front side.
    // 1. UNQUALIFIED TARGET WORDS SPAN BOTH SIDES. The printed text is "each of up to 2 OTHER OFFICIAL
    //    units" — no "friendly". SWU says "friendly" when it means it (SOR_094 "another FRIENDLY unit"),
    //    so this reaches the OPPONENT's Officials too. Handing the enemy Experience is a real if unusual
    //    choice, and "up to 2" lets the player take fewer. `SWUAllUnits('my')` is friendly-only;
    //    the unqualified pool passes null.
    // 2. TRAIT LOOKUP MUST BE OBJECT-AWARE. `HasTrait($obj->CardID, …)` reads the PRINTED trait list of a
    //    bare CardID, so a unit GRANTED the Official trait (or a deployed leader with a trait override)
    //    was invisible. `TraitContains($obj, …)` is the object-aware check and falls back to the printed
    //    list when there is no override.
    foreach (SWUAllUnits(null) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $selfUID && TraitContains($o, 'Official')) $officials[] = $mz;
    }
    if (empty($officials)) return;
    DecisionQueueController::AddDecision(intval($player), "MZMULTICHOOSE", "0|2|" . implode('&', $officials), 1, tooltip: "Give_Experience_to_up_to_2_other_Official_units");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_084#0", 1, dontSkipOnPass: 1);
};

$customDQHandlers["SEC_084#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    foreach (array_slice(array_values(array_filter(explode('&', (string)$lastDecision), fn($m) => $m !== '' && $m !== '-')), 0, 2) as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) DoGiveExperienceToken(intval($player), $mz);
    }
};
