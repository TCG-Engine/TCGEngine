<?php
// TWI_227
// Cost 4 - Prisoner of War - [Cunning]
// Text: A friendly unit captures an enemy non-leader, non-Vehicle unit. If the enemy unit costs less than the friendly unit, create 2 Battle Droid tokens. (Put the captured card facedown under the friendly unit until that unit leaves play.)

// TWI_227 Prisoner of War — step 0: chose the friendly capturer. Offer the enemy non-leader,
// non-Vehicle units in the same arena. $parts unused; carry the captor UID into step 1.
$customDQHandlers["TWI_227#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $captor = GetZoneObject($lastDecision);
    if (SWUObjGone($captor)) return;
    $captorUID = intval($captor->UniqueID ?? -1);
    $location  = $captor->Location ?? 'GroundArena';
    $enemyZone = (strpos($location, 'Space') !== false) ? 'theirSpaceArena' : 'theirGroundArena';
    $targets = [];
    foreach (ZoneSearch($enemyZone, NonLeaderUnitFilter) as $emz) {
        $eo = GetZoneObject($emz);
        if ($eo !== null && empty($eo->removed) && !HasTrait($eo->CardID, 'Vehicle')) $targets[] = $emz;
    }
    if (empty($targets)) return; // fizzle — no valid target
    SWUQueueChooseTarget(intval($player), $targets,
        'Choose_an_enemy_non-leader_non-Vehicle_unit_to_capture', 'TWI_227#1|' . $captorUID);
};

// TWI_227 step 1: capture the chosen enemy; if it costs less than the captor, create 2 Battle Droids.
$customDQHandlers["TWI_227#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $captorUID = intval($parts[0] ?? -1);
    $captorMz  = SWUFindMzByUID($captorUID);
    if ($captorMz === null) return; // captor left play
    $captorObj  = GetZoneObject($captorMz);
    $targetObj  = GetZoneObject($lastDecision);
    if ($captorObj === null || SWUObjGone($targetObj)) return;
    $captorCost   = intval(CardCost($captorObj->CardID));
    $capturedCost = intval(CardCost($targetObj->CardID));
    DoCaptureUnit(intval($player), $captorMz, $lastDecision);
    if ($capturedCost < $captorCost) SWUCreateUnitTokens(intval($player), 'TWI_T01', 2);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_227:0"] = function($player, $mzID = '') {
// Prisoner of War — "A friendly unit captures an enemy non-leader, non-Vehicle
                          // unit. If the enemy unit costs less than the friendly unit, create 2 Battle Droid tokens."
            global $playerID;
            $playerID = intval($player);
            $capturers = [];
            foreach (['myGroundArena' => 'theirGroundArena', 'mySpaceArena' => 'theirSpaceArena'] as $myZone => $theirZone) {
                $hasTarget = false;
                foreach (ZoneSearch($theirZone, NonLeaderUnitFilter) as $emz) {
                    $eo = GetZoneObject($emz);
                    if ($eo !== null && empty($eo->removed) && !HasTrait($eo->CardID, 'Vehicle')) { $hasTarget = true; break; }
                }
                if (!$hasTarget) continue;
                foreach (ZoneSearch($myZone, AnyUnitFilter) as $fmz) {
                    $fo = GetZoneObject($fmz);
                    if ($fo !== null && empty($fo->removed)) $capturers[] = $fmz;
                }
            }
            if (empty($capturers)) return; // fizzle — no friendly unit with a valid target
            SWUQueueChooseTarget(intval($player), array_values(array_unique($capturers)),
                'Choose_a_friendly_unit_to_capture_with', 'TWI_227#0');
            return;
};
