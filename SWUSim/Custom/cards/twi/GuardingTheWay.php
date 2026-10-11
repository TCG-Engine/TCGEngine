<?php
// TWI_074
// Cost 2 - Guarding the Way - [Vigilance]
// Text: Give a unit Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.) / If you have the initiative, also give that unit +2/+2 for this phase.

// TWI_074 Guarding the Way — give the chosen unit Sentinel this phase; if the caster has the
// initiative, also +2/+2 this phase.
$customDQHandlers["TWI_074#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    global $playerID;
    $playerID = intval($player);
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    AddTurnEffect($lastDecision, 'SENTINEL');   // grants Sentinel this phase (registry GRANT_KEYWORD)
    if (HasInitiative(intval($player))) SWUApplyPhaseBuff($lastDecision, 2, 2, '');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_074:0"] = function($player, $mzID = '') {
// Guarding the Way — "Give a unit Sentinel for this phase. If you have the
                          // initiative, also give that unit +2/+2 for this phase."
            global $playerID;
            $playerID = intval($player);
            // ⚠ UNQUALIFIED pool = the WHOLE table. NOT my*+their*: `their*` excludes a Team Suns
            // teammate, so that pairing leaves their units in NEITHER list and they silently drop out
            // of the pool. SWUAllUnits() starts from 'team' (degrades to 'my' outside a team game, so
            // Premier is byte-identical). See memory: unqualified pools miss teammates.
            $targets = SWUAllUnits();
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Give_a_unit_Sentinel_this_phase", "TWI_074#0");
            return;
};
