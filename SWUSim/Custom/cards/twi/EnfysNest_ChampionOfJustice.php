<?php
// TWI_198
// Cost 7 - Enfys Nest - Champion of Justice - [Cunning,Heroism] - Power 5 - HP 7
// Text: Saboteur / When Played/On Attack: You may return an enemy non-leader unit with less power than this unit to its owner's hand.

// TWI_198 Enfys Nest — "When Played/On Attack: You may return an enemy non-leader unit with less power
// than this unit to its owner's hand." (Saboteur is a keyword.)
$whenPlayedAbilities["TWI_198:0"] = $onAttackAbilities["TWI_198:0"] = function($player, $mzID) {
    $self = GetZoneObject($mzID);
    if (SWUObjGone($self)) return;
    $myPower = intval(ObjectCurrentPower($self));
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'BOUNCE_UNIT', 'side' => 'their', 'nonLeader' => true, 'may' => true,
        'extraFilter' => fn($o) => intval(ObjectCurrentPower($o)) < $myPower,
        'question' => "You_may_return_a_weaker_enemy_unit_to_hand", 'prompt' => "Return_a_weaker_enemy_unit",
    ]);
};
