<?php
// SEC_091
// Cost 4 - Corporate Warmongering - [Command,Villainy]
// Text: Give a friendly unit +3/+3 for this phase. Give each other friendly unit +1/+1 for this phase.

// SEC_091 Corporate Warmongering (event) — Give a friendly unit +3/+3 this phase; give each OTHER
// friendly unit +1/+1 this phase. Pick the +3/+3 recipient, then buff the rest.
$customDQHandlers["SEC_091#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $chosen = GetZoneObject($lastDecision);
    $chosenUID = $chosen ? intval($chosen->UniqueID ?? 0) : -1;
    SWUApplyPhaseBuff($lastDecision, 3, 3, '');
    foreach (SWUAllUnits('my') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed) && intval($o->UniqueID ?? 0) !== $chosenUID) SWUApplyPhaseBuff($mz, 1, 1, '');
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_091:0"] = function($player, $mzID = '') {
// Corporate Warmongering — "Give a friendly unit +3/+3 for this phase. Give
                          // each other friendly unit +1/+1 for this phase." Pick the +3/+3 recipient.
            global $playerID; $playerID = intval($player);
            $friendly = SWUFriendlyUnits();
            if (empty($friendly)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Give_a_friendly_unit_+3/+3_(others_+1/+1)", "SEC_091#0");
            return;
};
