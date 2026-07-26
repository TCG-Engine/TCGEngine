<?php
// JTL_126
// Cost 2 - Eject - [Command]
// Text: Detach a Pilot upgrade, move it to the ground arena as a unit, and exhaust it. Draw a card.

// ── JTL_126 Eject continuation — detach the chosen host's Pilot, move it to the ground arena as an
// exhausted unit (owner's arena), then the event's controller draws. (Move/attach subsystem.)
$customDQHandlers["JTL_126#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    $host = GetZoneObject($lastDecision);
    if (SWUObjGone($host)) return;
    $idx = _SWUFindPilotSubcard($host);
    if ($idx === null) return;
    SWUMoveUpgradeToUnit($lastDecision, $idx, 'GroundArena', true);
    DoDrawCard(intval($player), 1);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_126:0"] = function($player, $mzID = '') {
// Eject — Detach a Pilot upgrade, move it to the ground arena as a unit, and
                          // exhaust it. Draw a card. (Continuation JTL_126.) The choice is the host
                          // vehicle (a Vehicle holds at most one Pilot), across both players' arenas.
            global $playerID;
            $playerID = intval($player);
            $hosts = [];
            foreach (['myGroundArena', 'mySpaceArena', 'theirGroundArena', 'theirSpaceArena'] as $z) {
                foreach (ZoneSearch($z, AnyUnitFilter) as $mz) {
                    $o = GetZoneObject($mz);
                    if ($o !== null && empty($o->removed) && _SWUFindPilotSubcard($o) !== null) $hosts[] = $mz;
                }
            }
            // "Draw a card" is a separate, unconditional clause — it happens even if there is no Pilot to
            // detach (the detach simply does nothing). Draw here on the no-host path (the with-host path
            // draws in the JTL_126#0 continuation after the detach).
            if (empty($hosts)) { DoDrawCard(intval($player), 1); return; }
            SWUQueueChooseTarget(intval($player), $hosts, "Detach_a_Pilot_upgrade", "JTL_126#0");
            return;
};
