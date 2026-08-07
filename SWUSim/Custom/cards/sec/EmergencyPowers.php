<?php
// SEC_040
// Cost 1 - Emergency Powers - [Vigilance,Villainy]
// Text: Choose a non-leader unit and pay any number of resources. For each resource paid this way, give an Experience token to the chosen unit.

// SEC_040 Emergency Powers — chosen non-leader unit; pay any number of resources → that many Exp tokens.
$customDQHandlers["SEC_040#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $maxX = SWUResourceCount(intval($player), readyOnly: true); // resources only — see the note on SEC_040#1
    if ($maxX <= 0) return;
    DecisionQueueController::AddDecision(intval($player), "NUMBERCHOOSE", "0|" . $maxX, 1, tooltip: "Pay_any_number_of_resources_(1_Experience_each)");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "SEC_040#1|" . intval($o->UniqueID ?? 0), 1);
};

$customDQHandlers["SEC_040#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $x = intval($lastDecision);
    if ($x <= 0) return;
    // ⚠ SCALED-EFFECT COST — resources ONLY, never Credit tokens / SEC_122 Droids.
    // The magnitude keys off "resources paid this way", and a Credit is NOT a resource (CR 3.13):
    // defeating one pays 1 less, it does not become a resource paid. So a Credit can pay this CARD's
    // own play cost (the normal play path), but must never scale this effect. Deliberate exception to
    // the engine-wide SWUPayInlineAbilityCost conversion — do not "fix" it back.
    if (!SWUExhaustResources(intval($player), $x)) return;
    $mz = SWUFindMzByUID(intval($parts[0] ?? 0));
    if ($mz === null) return;
    for ($i = 0; $i < $x; $i++) DoGiveExperienceToken(intval($player), $mz);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_040:0"] = function($player, $mzID = '') {
// Emergency Powers — "Choose a non-leader unit and pay any number of resources.
                          // For each resource paid, give an Experience token to the chosen unit."
            global $playerID; $playerID = intval($player);
            $units = array_merge(ZoneSearch("myGroundArena", NonLeaderUnitFilter), ZoneSearch("mySpaceArena", NonLeaderUnitFilter),
                                 ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter));
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Choose_a_non-leader_unit", "SEC_040#0");
            return;
};
