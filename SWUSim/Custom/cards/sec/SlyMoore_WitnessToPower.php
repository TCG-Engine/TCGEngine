<?php
// SEC_033
// Cost 4 - Sly Moore - Witness to Power - [Vigilance,Villainy] - Power 2 - HP 6
// Text: When Played: For this phase, each enemy unit gets -2/-0 while it's attacking a base. / Plot (When you deploy a leader, you may play this card from your resources, paying its cost. Replace it with the top card of your deck.)

// SEC_033 Sly Moore — When Played: for this phase, each enemy unit gets -2/-0 while attacking a base.
// Mark each enemy in play now; the -2 is applied in SWUCombatDamage when that unit attacks a base. (Plot auto.)
$whenPlayedAbilities["SEC_033:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (SWUAllUnits('their') as $mz) {
        $o = GetZoneObject($mz);
        if ($o !== null && empty($o->removed)) AddTurnEffect($mz, 'SWU_SEC033');
    }
};
