<?php
// SEC_255
// Cost 6 - Remote Escort Tank - [Heroism] - Power 5 - HP 5
// Text: When Played: Give a unit Sentinel for this phase. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_255 Remote Escort Tank — When Played: give a unit Sentinel for this phase.
$whenPlayedAbilities["SEC_255:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GRANT_PHASE_KEYWORD|SENTINEL^SEC_255', 'prompt'=>"Give_a_unit_Sentinel_for_this_phase"]);
};
