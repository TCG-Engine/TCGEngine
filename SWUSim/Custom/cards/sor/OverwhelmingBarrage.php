<?php
// SOR_092
// Cost 5 - Overwhelming Barrage - [Command,Villainy]
// Text: Give a friendly unit +2/+2 for this phase. Then, it deals damage equal to its power divided as you choose among any number of other units.

// SOR_092 Overwhelming Barrage — the chosen friendly dealer ($lastDecision) gets +2/+2 for this
// phase, then deals damage equal to its BUFFED power split among any number of OTHER units.
$customDQHandlers["SOR_092#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    SWUApplyPhaseBuff($lastDecision, 2, 2, "SOR_092");          // +2/+2 for this phase (buff stands even if no targets)
    $dealer = GetZoneObject($lastDecision);
    if (SWUObjGone($dealer)) return;
    $dealerUID = intval($dealer->UniqueID ?? 0);
    $power = ObjectCurrentPower($dealer);                       // power AFTER the buff
    if ($power <= 0) return;
    $targets = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? -1) === $dealerUID) continue; // "other units" — exclude the dealer
        $targets[] = $mz;
    }
    if (empty($targets)) return;                               // no other units → buff applied, no damage
    DecisionQueueController::AddDecision($player, "MZSPLITASSIGN", $power . "|" . implode("&", $targets), 1, tooltip:"Divide_damage_among_other_units");
    DecisionQueueController::AddDecision($player, "CUSTOM", "SPLIT_DAMAGE", 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_092:0"] = function($player, $mzID = '') {
// Overwhelming Barrage — give a friendly unit +2/+2 this phase, then it
            // deals damage equal to its (buffed) power divided among any number of OTHER units.
            global $playerID;
            $playerID = intval($player);
            $friendly = SWUFriendlyUnits();
            if (empty($friendly)) return; // no friendly unit → fizzle
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_unit_to_buff", "SOR_092#0");
            return;
};
