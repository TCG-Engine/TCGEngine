<?php
// SOR_178
// Cost 2 - Cartel Spacer - [Cunning,Villainy] - Power 2 - HP 3
// Text: When Played: If you control another [Cunning] unit, exhaust an enemy unit that costs 4 or less.

// SOR_178 Cartel Spacer — When Played: If you control another [Cunning] unit, exhaust an
// enemy unit that costs 4 or less. Automatic.
$whenPlayedAbilities["SOR_178:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self    = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self);
    $hasCunning = false;
    foreach (array_merge(
        ZoneSearch('myGroundArena', AnyUnitFilter),
        ZoneSearch('mySpaceArena',  AnyUnitFilter)
    ) as $mz) {
        $o = GetZoneObject($mz);
        if (SWUObjGone($o)) continue;
        if (intval($o->UniqueID ?? -2) === $selfUID) continue;                  // "another"
        if (strpos(CardAspect($o->CardID) ?? '', 'Cunning') !== false) { $hasCunning = true; break; }
    }
    if (!$hasCunning) return;
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'EXHAUST_UNIT', 'side' => 'their',
        'extraFilter' => fn($o) => intval(CardCost($o->CardID) ?? 99) <= 4,
        'prompt' => 'Exhaust_an_enemy_unit_(cost_4_or_less)',
    ]);
};
