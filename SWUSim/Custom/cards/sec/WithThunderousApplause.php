<?php
// SEC_129
// Cost 3 - With Thunderous Applause - [Command]
// Text: Give a unit +2/+2 for this phase. / You may disclose Command (reveal a card from your hand with this aspect icon). If you do, give another unit +2/+2 for this phase.

// SEC_129 With Thunderous Applause continuations — #0: buff the first chosen unit +2/+2, then offer
// the Command disclose; #1: buff ANOTHER unit +2/+2.
$customDQHandlers["SEC_129#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $firstUID = intval($o->UniqueID ?? 0);
    SWUApplyPhaseBuff($lastDecision, 2, 2, 'SEC_129');
    SWUQueueDisclose(intval($player), ['Command'], "SEC_129#1|{$firstUID}",
        "Disclose_Command_to_give_another_unit_+2/+2");
};

$customDQHandlers["SEC_129#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $firstUID = intval($parts[0] ?? 0);
    $others = [];
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? 0) === $firstUID) continue;   // "another unit"
        $others[] = $mz;
    }
    if (empty($others)) return;
    SWUQueueChooseTarget(intval($player), $others, "Give_another_unit_+2/+2_for_this_phase",
        "APPLY_PHASE_BUFF|2|2|SEC_129");
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_129:0"] = function($player, $mzID = '') {
// With Thunderous Applause — "Give a unit +2/+2 for this phase. You may
                          // disclose Command → give ANOTHER unit +2/+2 for this phase."
            global $playerID; $playerID = intval($player);
            $units = SWUAllUnits();
            if (empty($units)) return;
            SWUQueueChooseTarget(intval($player), $units, "Give_a_unit_+2/+2_for_this_phase", "SEC_129#0");
            return;
};
