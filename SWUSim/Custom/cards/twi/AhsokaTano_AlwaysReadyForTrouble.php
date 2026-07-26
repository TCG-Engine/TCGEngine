<?php
// TWI_194
// Cost 3 - Ahsoka Tano - Always Ready For Trouble - [Cunning,Heroism] - Power 3 - HP 4
// Text: While you control fewer units than an opponent (including this unit), this unit gains Ambush. / Action [2 resources]: Return this unit and each upgrade on her to their owners' hands.

// TWI_194 Ahsoka Tano — "Action [2 resources]: Return this unit and each upgrade on her to their
// owners' hands." No exhaust; the framework pays the 2 resources.
$unitActionCostKind["TWI_194"] = 'none';

$unitActionResourceCosts["TWI_194"] = 2;

$unitAbilities["TWI_194"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    if ($self !== null && !empty($self->Subcards) && is_array($self->Subcards)) {
        // Snapshot upgrade CardIDs first (SWUReturnUpgradeToHand mutates the Subcards array).
        $upCids = [];
        foreach ($self->Subcards as $sub) {
            $scid = is_array($sub) ? ($sub['CardID'] ?? '')  : ($sub->CardID ?? '');
            $isRem = is_array($sub) ? !empty($sub['removed']) : !empty($sub->removed);
            $isCap = is_array($sub) ? !empty($sub['IsCaptive']) : !empty($sub->IsCaptive);
            if ($isRem || $isCap || $scid === '') continue;
            if (strpos(strtolower(CardType($scid) ?? ''), 'token') !== false) continue; // tokens set aside
            $upCids[] = $scid;
        }
        foreach ($upCids as $scid) SWUReturnUpgradeToHand($mzID, $scid, intval($player));
    }
    SWUBounceUnit(intval($player), $mzID); // returns Ahsoka to her owner's hand
    SWUAfterAction(intval($player));
};
