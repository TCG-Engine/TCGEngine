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
            $eligible = [];
            foreach (array_merge(ZoneSearch("myGroundArena", AnyUnitFilter), ZoneSearch("mySpaceArena", AnyUnitFilter)) as $mz) {
                $o = GetZoneObject($mz);
                if (SWUObjGone($o)) continue;
                $uid = intval($o->UniqueID ?? 0);
                if (GlobalEffectCount(intval($player), 'SWU_PLAYED_UNIT_' . $uid) > 0) continue;   // entered this phase
                if (GlobalEffectCount(intval($player), 'SWU_UNIT_ATTACKED_' . $uid) > 0) continue;  // attacked this phase
                $eligible[] = $mz;
            }
            if (empty($eligible)) { SWUCreateUnitToken(intval($player), 'SEC_T01'); return; }
            SWUQueueMayChooseTarget(intval($player), $eligible, "Ready_a_unit?", "Choose_a_unit_to_ready", "SEC_177#0");
            return;
};
