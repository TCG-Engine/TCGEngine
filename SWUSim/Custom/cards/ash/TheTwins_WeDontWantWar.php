<?php
// ASH_127
// Cost 4 - The Twins - We Don't Want War - [Command] - Power 2 - HP 7
// Text: When Played/On Attack: You may give another friendly unit Sentinel for this phase. / When another friendly unit is defeated: Heal 1 damage from your base.

// ASH_127 The Twins — When Played/On Attack: you may give another friendly unit Sentinel for this phase.
// (The "When another friendly unit is defeated: heal 1 from base" reaction is in SWUCollectLeavePlayReactions.)
$whenPlayedAbilities["ASH_127:0"] = $onAttackAbilities["ASH_127:0"] = function($player, $mzID) {
    SWUOfferUnitTarget($player, $mzID, ['continuation'=>'GRANT_PHASE_KEYWORD|ASH_127', 'side'=>'my', 'excludeSelf'=>true, 'may'=>true, 'question'=>"Give_another_friendly_unit_Sentinel_this_phase?", 'prompt'=>"Choose_a_unit"]);
};
