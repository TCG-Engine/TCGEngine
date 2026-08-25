<?php
// SEC_236
// Cost 3 - Undercover Operation - [Cunning]
// Text: Ready a unit that was played this phase. If it costs 3 or less, create a Spy token.

// SEC_236 Undercover Operation — ready the chosen (played-this-phase) unit; if it cost 3 or less, create a Spy.
$customDQHandlers["SEC_236#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    $cost = intval(CardCost($o->CardID ?? ''));
    OnReadyCard(intval($player), $lastDecision);
    if ($cost <= 3) SWUCreateUnitToken(intval($player), 'SEC_T01');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_236:0"] = function($player, $mzID = '') {
// Undercover Operation — "Ready a unit that was played this phase. If it costs
                          // 3 or less, create a Spy token."
            global $playerID; $playerID = intval($player);
            // "Ready A UNIT that was played this phase" — UNQUALIFIED, spans the WHOLE TABLE
            // (USER RULING 2026-08-25). Previously narrowed to the caster's own board, wrong in
            // 2-player too. The SWU_PLAYED_UNIT_ flag lives on the unit's CONTROLLER — see
            // SWUUnitPlayedThisPhase — so reading it against $player missed every foreign unit.
            $eligible = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (SWUUnitPlayedThisPhase($o)) $eligible[] = $mz;
            }
            if (empty($eligible)) return;
            SWUQueueChooseTarget(intval($player), $eligible, "Ready_a_unit_played_this_phase", "SEC_236#0");
            return;
};
