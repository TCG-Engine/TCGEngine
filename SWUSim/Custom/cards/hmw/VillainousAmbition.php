<?php
// HMW_252 Villainous Ambition — Upgrade +2/+0, cost 2, [Villainy], Innate.
// "When Played: If attached unit is a Villainy unit, you may deal 2 damage to a unit."
// The When Played dispatches with the HOST as $mzID. "a Villainy unit" reads the host's aspects; the
// damage target is any unit, the host included.

$whenPlayedAbilities["HMW_252:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $host = GetZoneObject($mzID);
    if (SWUObjGone($host) || strpos((string)CardAspect($host->CardID ?? ''), 'Villainy') === false) return;
    SWUOfferUnitTarget(intval($player), $mzID, [
        'continuation' => 'DEAL_UNIT_DAMAGE',
        'amount'       => 2,
        'may'          => true,
        'question'     => 'Deal_2_damage_to_a_unit?',
        'prompt'       => 'Choose_a_unit',
    ]);
};
