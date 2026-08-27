<?php
// TWI_160
// Cost 2 - Vanguard Droid Bomber - [Aggression] - Power 2 - HP 2
// Text: When Played: If you control another Separatist unit, deal 2 damage to an enemy base.

// TWI_160 Vanguard Droid Bomber — "When Played: If you control another Separatist unit, deal 2 damage to
// an enemy base."
$whenPlayedAbilities["TWI_160:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $self = GetZoneObject($mzID);
    $selfUID = SWUObjUID($self, 0);
    $hasOther = false;
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->UniqueID ?? 0) !== $selfUID && HasTrait($u->CardID ?? '', 'Separatist')) { $hasOther = true; break; }
    }
    // "AN enemy base" names no seat — the caster picks which. SWUQueueChooseOpponent auto-resolves to an
    // invisible PASSPARAMETER at one eligible opponent, so Premier is byte-identical.
    if ($hasOther) SWUQueueChooseOpponent(intval($player), 'TWI_160#BASE|' . $selfUID, "Deal_2_to_which_opponent's_base?");
};

$customDQHandlers["TWI_160#BASE"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp <= 0) return;
    // 3rd arg is the acting player, exactly as before the conversion — not the bomber object.
    SWUDealDamageToBase(2, $opp, intval($player));
};
