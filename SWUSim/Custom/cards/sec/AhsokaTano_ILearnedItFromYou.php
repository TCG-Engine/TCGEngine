<?php
// SEC_096
// Cost 3 - Ahsoka Tano - I Learned It from You - [Command,Heroism] - Power 2 - HP 5
// Text: When this unit completes an attack (and survives): You may disclose CommandHeroism. If you do, attack with another unit.

// SEC_096 Ahsoka Tano — When this unit completes an attack (and survives): you may disclose
// CommandHeroism → attack with another unit.
$onAttackEndAbilities["SEC_096:0"] = function($player, $mzID) {
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    SWUQueueDisclose(intval($player), ['Command', 'Heroism'], "SEC_096#0|{$selfUID}",
        "Disclose_CommandHeroism_to_attack_with_another_unit");
};

$customDQHandlers["SEC_096#0"] = function($player, $parts, $lastDecision) {
    $selfUID = intval($parts[0] ?? 0);
    SWUQueueAnotherAttack(intval($player), false, false, 0, $selfUID);   // any unit, mandatory once disclosed
};
