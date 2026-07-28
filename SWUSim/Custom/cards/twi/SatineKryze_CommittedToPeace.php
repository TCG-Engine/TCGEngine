<?php
// TWI_047
// Cost 4 - Satine Kryze - Committed to Peace - [Vigilance,Heroism] - Power 0 - HP 6
// Text: Each unit (including enemy units) gains: "Action [Exhaust]: Discard cards from an opponent's deck equal to half this unit's remaining HP, rounded up."

// TWI_047 Satine Kryze — "Each unit (including enemy units) gains: Action [Exhaust]: Discard cards from
// an opponent's deck equal to half this unit's remaining HP, rounded up." The grant is surfaced on every
// unit by SWUGetUnitActionProvider's _SWUSatineInPlay() fallback; each unit's controller uses it, milling
// THEIR opponent's deck by ceil(remainingHP/2). 'exhaust' cost kind (requires ready, exhausts the unit).
$unitActionCostKind["TWI_047"] = 'exhaust';

$unitAbilities["TWI_047"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    if ($obj !== null && empty($obj->removed)) {
        $remHP  = max(0, intval(ObjectCurrentHP($obj)) - intval($obj->Damage ?? 0));
        $amount = intval(ceil($remHP / 2));
        $opp    = OtherPlayer(intval($player));
        for ($i = 0; $i < $amount; $i++) SWUMillTopCard($opp);
    }
    SWUAfterAction(intval($player));
};
