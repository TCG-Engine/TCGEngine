<?php
// ASH_072
// Cost 2 - Doctor Pershing - Dedicated to Research - [Vigilance] - Power 0 - HP 4
// Text: Support (When you play this unit, you may attack with another unit. It gains this unit's other abilities for this attack.) / On Attack: If this unit has 3 or more remaining HP, draw a card.

// ASH_072 Doctor Pershing — On Attack: if this unit has 3 or more remaining HP, draw a card.
$onAttackAbilities["ASH_072:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $remHP = intval(ObjectCurrentHP($self)) - intval($self->Damage ?? 0);
    if ($remHP >= 3) DoDrawCard(intval($player), 1);
};
