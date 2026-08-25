<?php
// TWI_199
// Cost 2 - Clear the Field - [Cunning,Heroism]
// Text: Choose a non-leader unit that costs 3 or less. Return it and each enemy non-leader unit with the same name as it to their owners' hands.

// TWI_199 Clear the Field — chosen unit: return it and each enemy (opponent's) non-leader with the same name.
$customDQHandlers["TWI_199#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $name = SWUObjectTitle($o);
    // "each ENEMY non-leader unit with the same name" — enemy of the ABILITY'S CONTROLLER (the caster),
    // which is the CR meaning of "enemy" and does not change with the chosen unit's owner. So picking an
    // opponent's unit still returns every OTHER opponent's same-name units, and never the caster's own.
    //
    // The fix is to STAY IN THE CASTER'S FRAME and search their*: ZoneSearch already fans `their<Zone>`
    // out across every live opponent above two seats, so it does the work for free. The old code flipped
    // $playerID to a single OtherPlayer() and searched my* under that frame, which reached exactly ONE
    // opponent — at four seats two of the three were simply never checked.
    // At two seats their* IS that one opponent's arena, so Premier is byte-identical (I1) and the
    // $sp/$playerID save-restore dance is no longer needed at all.
    // Snapshot by UID before any bounce shifts indices.
    $sameUids = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $eo = GetZoneObject($mz);
            if ($eo !== null && empty($eo->removed) && SWUObjectTitle($eo) === $name) $sameUids[] = intval($eo->UniqueID ?? 0);
        }
    }
    SWUBounceUnit(intval($player), $lastDecision); // return the chosen unit
    foreach ($sameUids as $uid) {
        $mz = SWUFindMzByUID($uid);
        if ($mz !== null) SWUBounceUnit(intval($player), $mz);
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_199:0"] = function($player, $mzID = '') {
// Clear the Field — "Choose a non-leader unit that costs 3 or less. Return it and
                          // each enemy non-leader unit with the same name as it to their owners' hands."
            global $playerID; $playerID = intval($player);
            $targets = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval(CardCost($o->CardID ?? '')) <= 3) $targets[] = $mz;
                }
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Choose_a_non-leader_unit_costing_3_or_less", "TWI_199#0");
            return;
};
