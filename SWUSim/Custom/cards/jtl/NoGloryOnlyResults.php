<?php
// JTL_043
// Cost 5 - No Glory, Only Results - [Vigilance,Villainy]
// Text: Take control of a non-leader unit, then defeat it.

// ── JTL_043 No Glory, Only Results — take control of the chosen non-leader unit, then defeat it. ──────
$customDQHandlers["JTL_043#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision === null || $lastDecision === '-' || $lastDecision === '' || $lastDecision === 'PASS') return;
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($lastDecision);
    if (SWUObjGone($obj)) return;
    $newMz = SWUTakeControlOfUnit(intval($player), $lastDecision);   // unit moves into the caster's arena
    if ($newMz === '') return;                                       // take control blocked (LAW_149 Rey) — nothing to defeat
    // ⚠ "THEN defeat it" IS QUEUED, NOT INLINE. Taking control can hand the caster a SECOND copy of a
    // unique card, and CR 29.3.3 resolves that immediately — before this clause. SWUTakeControlOfUnit
    // queues that choice, so defeating here in straight-line PHP would run BEFORE it and the caster
    // would never be asked (the duplicate is gone by action close, so the backstop never sees it).
    // Queued, the order is: uniqueness choice → this defeat.
    //
    // Addressed by UID, not mzID: the uniqueness defeat compacts the arena, so $newMz can by then name
    // a DIFFERENT unit. If the caster resolved uniqueness by defeating this very copy, the lookup
    // finds nothing and the clause correctly does nothing.
    $stolenObj = GetZoneObject($newMz);
    $stolenUid = intval($stolenObj->UniqueID ?? 0);
    if ($stolenUid <= 0) { SWUDefeatUnit(intval($player), $newMz); return; }   // no UID to track: defeat now
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "JTL_043#1|{$stolenUid}", 1);
};

// "…then defeat it" — resolved after any uniqueness choice the control change forced (see above).
$customDQHandlers["JTL_043#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $uid = intval($parts[0] ?? 0);
    if ($uid <= 0) return;
    $mz = SWUFindMzByUID($uid);
    if ($mz === null || $mz === '') return;              // already defeated by the uniqueness rule
    $obj = GetZoneObject($mz);
    if (SWUObjGone($obj)) return;
    SWUDefeatUnit(intval($player), $mz);                 // now friendly, so it lands in its OWNER's discard
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_043:0"] = function($player, $mzID = '') {
// No Glory, Only Results — "Take control of a non-leader unit, then defeat it."
            global $playerID;
            $playerID = intval($player);
            // ⚠ UNQUALIFIED pool = the WHOLE table. NOT my*+their*: `their*` excludes a Team Suns
            // teammate, so that pairing leaves their units in NEITHER list and they silently drop out
            // of the pool. SWUAllUnits() starts from 'team' (degrades to 'my' outside a team game, so
            // Premier is byte-identical). See memory: unqualified pools miss teammates.
            $targets = SWUAllUnits(null, null, NonLeaderUnitFilter);
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Take_control_of_and_defeat_a_non-leader_unit", "JTL_043#0");
            return;
};
