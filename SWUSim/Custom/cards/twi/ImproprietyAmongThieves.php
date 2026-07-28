<?php
// TWI_204
// Cost 4 - Impropriety Among Thieves - [Cunning,Cunning]
// Text: Choose a ready non-leader unit controlled by each player. If you do, each player takes control of the chosen unit controlled by the player to their right. At the start of the regroup phase, each player takes control of each unit they own that was chosen for this ability.

// TWI_204 Impropriety Among Thieves — step 1: after the caster picks THEIR ready non-leader unit, capture
// its UID and offer the opponent's ready non-leader units for the second pick.
$customDQHandlers["TWI_204#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $mine = GetZoneObject($lastDecision);
    $myUid = ($mine !== null && empty($mine->removed)) ? intval($mine->UniqueID ?? 0) : 0;
    if ($myUid <= 0) return;
    $theirs = [];
    foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
        foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
            $o = GetZoneObject($mz);
            if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && !IsLeaderUnit($o)) $theirs[] = $mz;
        }
    }
    if (empty($theirs)) return; // opponent has no ready non-leader unit → fizzle
    SWUQueueChooseTarget(intval($player), $theirs, "Choose_the_opponents_ready_non-leader_unit", "TWI_204#1|" . $myUid);
};

// TWI_204 — step 2: swap control (2P). The caster takes the opponent's chosen unit; the opponent takes
// the caster's chosen unit. Both are marked TEMPORARY_STEAL so they return to their owners at the start
// of the regroup phase (the "each player takes control of each unit they own that was chosen" clause).
$customDQHandlers["TWI_204#1"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID; $playerID = intval($player);
    $myUid   = intval($parts[0] ?? 0);
    $theirObj = GetZoneObject($lastDecision);
    $theirUid = ($theirObj !== null && empty($theirObj->removed)) ? intval($theirObj->UniqueID ?? 0) : 0;
    if ($myUid <= 0 || $theirUid <= 0) return;
    $opp = OtherPlayer(intval($player));
    // 1) caster takes control of the opponent's chosen unit (moves into the caster's arena).
    $newB = SWUTakeControlOfUnit(intval($player), $lastDecision);
    if ($newB !== '') AddTurnEffect($newB, 'TEMPORARY_STEAL');
    // 2) opponent takes control of the caster's chosen unit (re-find by UID — the arena reindexed).
    $myMz = SWUFindMzByUID($myUid);
    if ($myMz !== null && $myMz !== '') {
        $newA = SWUTakeControlOfUnit($opp, $myMz);
        // SWUTakeControlOfUnit returns the new mzID in the NEW CONTROLLER's frame (it computes GetMzID
        // while $playerID = $opp). Set $playerID = $opp so AddTurnEffect resolves that "myGroundArena-N"
        // to the moved unit, not to a slot in the caster's arena.
        if ($newA !== '') {
            $playerID = $opp;
            AddTurnEffect($newA, 'TEMPORARY_STEAL');
            $playerID = intval($player);
        }
    }
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_204:0"] = function($player, $mzID = '') {
// Impropriety Among Thieves — "Choose a ready non-leader unit controlled by each
                          // player. If you do, each player takes control of the chosen unit controlled by
                          // the player to their right. At the start of the regroup phase, each player
                          // takes control of each unit they own that was chosen." In 2P, "the player to
                          // their right" is the opponent, so this is a control SWAP (temporary until
                          // regroup). The caster chooses one ready non-leader unit for EACH player.
            global $playerID; $playerID = intval($player);
            $mine = [];
            foreach (['myGroundArena', 'mySpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && !IsLeaderUnit($o)) $mine[] = $mz;
                }
            }
            $theirs = [];
            foreach (['theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && intval($o->Status ?? 0) === 1 && !IsLeaderUnit($o)) $theirs[] = $mz;
                }
            }
            // "If you do" — both players must have a valid ready non-leader unit, else the swap fizzles.
            if (empty($mine) || empty($theirs)) return;
            SWUQueueChooseTarget(intval($player), $mine, "Choose_your_ready_non-leader_unit", "TWI_204#0");
            return;
};
