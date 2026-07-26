<?php
// TWI_109
// Cost 3 - 501st Liberator - [Command] - Power 3 - HP 3
// Text: When Played: If you control another Republic unit, you may heal 3 damage from a base.

// TWI_109 501st Liberator — "When Played: If you control another Republic unit, you may heal 3 damage
// from a base."
$whenPlayedAbilities["TWI_109:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $hasOther = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID && HasTrait($u->CardID ?? '', 'Republic')) { $hasOther = true; break; }
    }
    if (!$hasOther) return;
    SWUQueueMayChooseTarget(intval($player), ["myBase-0", "theirBase-0"],
        "You_may_heal_3_damage_from_a_base", "Heal_3_damage_from_a_base", "HEAL_TARGET|3");
};
