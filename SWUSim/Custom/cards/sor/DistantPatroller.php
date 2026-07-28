<?php
// SOR_060
// Cost 2 - Distant Patroller - [Vigilance] - Power 2 - HP 1
// Text: When Defeated: You may give a Shield token to a [Vigilance] unit.

// SOR_060 Distant Patroller — When Defeated: You may give a Shield token to a [Vigilance] unit.
$whenDefeatedAbilities["SOR_060:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player),
        _SWUCollectUnits(-1, fn($o) => strpos(CardAspect($o->CardID) ?? '', 'Vigilance') !== false),
        'Give_a_Shield_to_a_Vigilance_unit?', 'Choose_a_Vigilance_unit_to_Shield', 'GIVE_SHIELD');
};
