<?php
// SHD_132
// Cost 7 - Choose Sides - [Command]
// Text: Choose a friendly non-leader unit and an enemy non-leader unit. Exchange control of those units.

// ─── SHD_132 Choose Sides (control exchange) ──────────────────────────────────
$customDQHandlers["SHD_132#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $f = GetZoneObject($lastDecision);
    if (SWUObjGone($f)) return;
    $fUID = intval($f->UniqueID ?? 0);
    $enemy = array_merge(ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter));
    if (empty($enemy)) return;
    SWUQueueChooseTarget(intval($player), $enemy, "Choose_an_enemy_non-leader_unit", "SHD_132#1||" . $fUID);
};

$customDQHandlers["SHD_132#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $fUID = intval($parts[1] ?? 0);
    $enemyObj = GetZoneObject($lastDecision);
    // ⚠ "Exchange control of those units" — the seat that receives the friendly unit is the CHOSEN ENEMY
    // UNIT'S controller, a DETERMINED seat known only once the pick lands. It was threaded in from the
    // offer builder as OtherPlayer($player) — literally seat 2 — so above two seats the caster took a
    // seat-4 unit while seat 2 received the friendly one: a three-way swap the card never describes.
    $opp = intval($enemyObj->Controller ?? 0);
    if ($opp <= 0) $opp = SWUMzOwner((string)$lastDecision, intval($player));
    $fMz = SWUFindMzByUID($fUID);
    if (SWUObjGone($enemyObj) || $fMz === null) return;
    $fObj = GetZoneObject($fMz);
    if (SWUObjGone($fObj)) return;
    SWUTakeControlOfUnit(intval($player), $lastDecision);   // caster takes the enemy unit
    $fMz2 = SWUFindMzByUID($fUID);                            // re-resolve (caster's arena shifted)
    if ($fMz2 !== null) SWUTakeControlOfUnit($opp, $fMz2);   // opponent takes the (former) friendly unit
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_132:0"] = function($player, $mzID = '') {
// Choose Sides — "Choose a friendly non-leader unit and an enemy non-leader unit.
                          // Exchange control of those units." (LAW_170 without the Credit-token half.)
            global $playerID; $playerID = intval($player);
            $friendly = SWUFriendlyUnits(null, NonLeaderUnitFilter);
            $enemy    = array_merge(ZoneSearch("theirGroundArena", NonLeaderUnitFilter), ZoneSearch("theirSpaceArena", NonLeaderUnitFilter));
            if (empty($friendly) || empty($enemy)) return;
            SWUQueueChooseTarget(intval($player), $friendly, "Choose_a_friendly_non-leader_unit", "SHD_132#0");
            return;
};
