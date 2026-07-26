<?php
// SEC_189
// Cost 3 - Lurking Snub Fighter - [Cunning,Villainy] - Power 2 - HP 3
// Text: When Played: You may exhaust a unit. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_189 Lurking Snub Fighter — When Played: you may exhaust a unit.
$whenPlayedAbilities["SEC_189:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $targets = array_values(SWUAllUnits());
    if (empty($targets)) return;
    SWUQueueMayChooseTarget(intval($player), $targets, "Exhaust_a_unit?", "Choose_a_unit", "EXHAUST_UNIT");
};
