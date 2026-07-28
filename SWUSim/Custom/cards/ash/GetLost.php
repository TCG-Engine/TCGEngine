<?php
// ASH_067
// Cost 4 - Get Lost - [Vigilance,Heroism]
// Text: Defeat an upgraded non-leader unit.

$whenPlayedAbilities["ASH_067:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true,
        'extraFilter' => fn($o) => _SWUIsUpgraded($o),
        'prompt' => "Defeat_an_upgraded_non-leader_unit",
    ]);
};
