<?php
// SHD_094
// Cost 6 - Palpatine's Return - [Command,Villainy]
// Text: Play a unit from your discard pile. / It costs 6 resources less. If it's a Force unit, / it costs 8 resources less instead.

// ─── SHD_094 Palpatine's Return (Event) continuation ──────────────────────────
// Play a unit from your discard for 6 less (8 less if it's a Force unit).
$customDQHandlers["SHD_094#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    if (!preg_match('/myDiscard-(\d+)/', (string)$lastDecision, $m)) return;
    $o = GetZoneObject($lastDecision);
    $discount = ($o !== null && HasTrait($o->CardID ?? '', 'Force')) ? 8 : 6;
    // EXPERIMENT: canonical ActivateCard path (full cost pipeline) vs SWUPlayDiscardUnitDiscounted.
    SWUNestedPlay(intval($player), $lastDecision, false, $discount);   // nested: outer event owns the after-action
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_094:0"] = function($player, $mzID = '') {
// Palpatine's Return — "Play a unit from your discard pile. It costs 6 less. If it's
                          // a Force unit, it costs 8 less instead."
            $targets = ZoneSearch("myDiscard", ["Unit"]);
            if (empty($targets)) return;
            SWUQueueChooseTarget(intval($player), $targets, "Play_a_unit_from_your_discard_(6_less,_8_if_Force)", "SHD_094#0");
            return;
};
