<?php
// LOF_170
// Cost 8 - Bendu - Do You Fear the Storm? - [Aggression] - Power 10 - HP 10
// Text: On Attack: Deal 3 damage to each other unit.

// LOF_170 Bendu — On Attack: deal 3 damage to each other unit. (Removal is deferred to CleanupRemovedCards,
// so iterating the snapshot and dealing is index-stable.)
$onAttackAbilities["LOF_170:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    foreach (SWUAllUnits() as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o) || intval($o->UniqueID ?? -1) === $selfUID) continue;
        SWUDealDamageToUnit($mz, 3, intval($player), $mzID); // $mzID = Bendu (Creature source) → LOF_108 rider
    }
};
