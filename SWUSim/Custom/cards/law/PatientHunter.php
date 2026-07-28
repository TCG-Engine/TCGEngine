<?php
// LAW_073
// Cost 4 - Patient Hunter - [Command,Cunning] - Power 3 - HP 3
// Text: When the regroup phase starts: You may give an Experience token to a non-leader unit. If you do, that unit can't ready during this regroup phase.

// LAW_073 Patient Hunter — give an Experience token to the chosen non-leader unit; it can't ready
// during this regroup phase (SWU_CANT_READY flag, consumed in the next ReadyPhase).
$customDQHandlers["LAW_073#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    DoGiveExperienceToken(intval($player), $lastDecision);
    $ctrl = intval($o->Controller ?? $player);
    $uid  = intval($o->UniqueID ?? 0);
    if ($uid > 0) AddGlobalEffects($ctrl, 'SWU_CANT_READY_' . $uid);
};
