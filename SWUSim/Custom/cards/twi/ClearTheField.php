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
    $name = CardTitle($o->CardID ?? '');
    $opp = OtherPlayer(intval($player));
    // Snapshot the opponent's same-name non-leader units by UID before any bounce shifts indices.
    $sameUids = [];
    $sp = $playerID; $playerID = $opp;
    foreach (['myGroundArena', 'mySpaceArena'] as $z) {
        foreach (ZoneSearch($z, NonLeaderUnitFilter) as $mz) {
            $eo = GetZoneObject($mz);
            if ($eo !== null && empty($eo->removed) && CardTitle($eo->CardID ?? '') === $name) $sameUids[] = intval($eo->UniqueID ?? 0);
        }
    }
    $playerID = $sp;
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
