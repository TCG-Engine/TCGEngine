<?php
// SEC_177
// Cost 2 - It's Not Over Yet - [Aggression]
// Text: You may ready a unit that didn't attack or enter play this phase. / Create a Spy token.

// SEC_177 It's Not Over Yet — ready the chosen eligible unit (if any), then create the Spy token (always).
$customDQHandlers["SEC_177#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision && $lastDecision !== '-' && $lastDecision !== 'PASS') OnReadyCard(intval($player), $lastDecision);
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_177:0"] = function($player, $mzID = '') {
// It's Not Over Yet — "You may ready a unit that didn't attack or enter play
                          // this phase. Create a Spy token." Offer the ready BEFORE creating the Spy so the
                          // new token (which entered this phase) is never an eligible ready target; the Spy
                          // is then created in SEC_177#0 (always).
            global $playerID; $playerID = intval($player);
            // "You may ready A UNIT that didn't attack or enter play this phase" — UNQUALIFIED, so it
            // names no controller and spans the WHOLE TABLE (USER RULING 2026-08-25). This was
            // previously narrowed to the caster's own board, which was wrong in 2-player too.
            // ⚠ The "this phase" flags live on each unit's CONTROLLER, so they must be read per unit —
            // reading them against $player returns false for every foreign unit and would make an
            // opponent's just-played unit look eligible. SWUUnitPlayedThisPhase/AttackedThisPhase do this.
            $eligible = [];
            foreach (SWUAllUnits() as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                if (SWUUnitPlayedThisPhase($o)) continue;    // entered play this phase
                if (SWUUnitAttackedThisPhase($o)) continue;  // attacked this phase
                $eligible[] = $mz;
            }
            if (empty($eligible)) { SWUCreateUnitToken(intval($player), 'SEC_T01'); return; }
            SWUQueueMayChooseTarget(intval($player), $eligible, "Ready_a_unit?", "Choose_a_unit_to_ready", "SEC_177#0");
            return;
};
