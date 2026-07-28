<?php
// SHD_105
// Cost 2 - Spark of Hope - [Command,Heroism]
// Text: Choose a unit in your discard pile. If it was defeated this phase, put it into play as a resource.

// ─── SHD_105 Spark of Hope (Event) continuation ───────────────────────────────
// Put the chosen (defeated-this-phase) unit into play as a resource.
$customDQHandlers["SHD_105#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $o = GetZoneObject($lastDecision);
    if (SWUObjGone($o)) return;
    SWURampResourceExhausted(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_105:0"] = function($player, $mzID = '') {
// Spark of Hope — "Choose a unit in your discard pile. If it was defeated this
                          // phase, put it into play as a resource."
            $targets = [];
            foreach (ZoneSearch('myDiscard', AnyUnitFilter) as $mz) {
                $o = GetZoneObject($mz);
                if ($o !== null && empty($o->removed)
                    && GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . ($o->CardID ?? '')) > 0) $targets[] = $mz;
            }
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Put_a_unit_defeated_this_phase_into_play_as_a_resource", "SHD_105#0");
            return;
};
