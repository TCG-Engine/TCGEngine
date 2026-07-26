<?php
// SEC_093
// Cost 1 - C-3PO - Anything I Might Do? - [Command,Heroism] - Power 1 - HP 3
// Text: Action [Exhaust, return this unit to its owner's hand]: Give a unit +2/+2 for this phase.

// SEC_093 C-3PO — Action [Exhaust, return this unit to its owner's hand]: Give a unit +2/+2 for this
// phase. The Exhaust is paid by SWUUnitAction; this handler pays the return-to-hand cost, then buffs.
$unitAbilities["SEC_093"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUBounceUnit(intval($player), $mzID);          // pay the "return this unit to hand" cost
    $targets = array_values(SWUAllUnits());         // any unit (the bounced C-3PO is already gone)
    if (empty($targets)) { SWUAfterAction($player); return; }
    SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_+2/+2_for_this_phase", "APPLY_PHASE_BUFF|2|2|");
    SWUQueueAfterAction($player);
};
