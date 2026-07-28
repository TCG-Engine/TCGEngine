<?php
// SOR_156
// Cost 1 - Benthic "Two Tubes" - Partisan Lieutenant - [Aggression] - Power 2 - HP 2
// Text: On Attack: Another friendly [Aggression] unit gains Raid 2 for this phase. (It gets +2/+0 while attacking.)

// SOR_156 Benthic "Two Tubes" — On Attack: "Another friendly [Aggression] unit gains Raid 2 for this
// phase." Aggression is an ASPECT (CardAspect), not a trait. Mandatory choose among friendly
// Aggression units (excluding self); fizzles if none.
$onAttackAbilities["SOR_156:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $host = GetZoneObject($mzID);
    $selfUID = ($host !== null) ? intval($host->UniqueID ?? 0) : 0;
    $targets = [];
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $selfUID) continue;
        if (strpos(CardAspect($o->CardID) ?? '', 'Aggression') !== false) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueChooseTarget(intval($player), $targets,
        "Grant_Raid_2_to_another_friendly_Aggression_unit", "SOR_156#0");
};

$customDQHandlers["SOR_156#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    AddTurnEffect($lastDecision, "SOR_156"); // CardID token; Raid value 2 comes from the registry, this phase
};
