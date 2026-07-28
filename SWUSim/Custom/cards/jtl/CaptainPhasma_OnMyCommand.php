<?php
// JTL_088
// Cost 5 - Captain Phasma - On My Command - [Command,Villainy] - Power 5 - HP 6
// Text: When Played/On Attack: You may give another First Order unit +2/+2 for this phase.

// ── JTL_088 Captain Phasma (unit) — When Played/On Attack: may give ANOTHER First Order unit +2/+2. ──
$whenPlayedAbilities["JTL_088:0"] = $onAttackAbilities["JTL_088:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUid = ($self !== null) ? intval($self->UniqueID ?? 0) : 0;
    $targets = [];
    // "another First Order unit" — no "friendly" qualifier, so ANY First Order unit (friendly OR enemy)
    // is a legal target (you may buff an enemy First Order unit).
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? 0) === $selfUid) continue;
        if (HasTrait($o->CardID, 'First Order')) $targets[] = $mz;
    }
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets,
        "You_may_give_another_First_Order_unit_+2/+2", "Give_+2/+2_this_phase", "APPLY_PHASE_BUFF|2|2|JTL_088");
};
