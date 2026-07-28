<?php
// JTL_229
// Diversion
// Text: Give a unit Sentinel for this phase. (Units in its arena can't attack your non-Sentinel units or your base.)

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_229:0"] = function($player, $mzID = '') {
// Diversion — give a unit Sentinel for this phase.
            SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GRANT_PHASE_KEYWORD|JTL_229', 'prompt'=>"Give_a_unit_Sentinel_this_phase"]);
            return;
};
