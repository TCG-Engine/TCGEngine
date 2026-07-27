<?php
// TWI_184
// Cost 5 - Tactical Droid Commander - [Cunning,Villainy] - Power 4 - HP 4
// Text: Exploit 2 / When you play another Separatist unit: You may exhaust a unit that costs the same as or less than the played unit.

// TWI_184 Tactical Droid Commander — "Exploit 2. When you play another Separatist unit: You may exhaust
// a unit that costs the same as or less than the played unit." Reactive own-play (collected in
// SWUCollectOwnPlayReactions); the played unit's cost rides in the trigger's mzID slot.
$customDQHandlers["TWI_184#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    $maxCost = intval($parts[0] ?? 0);
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'EXHAUST_UNIT', 'side' => 'any', 'may' => true,
        'extraFilter' => fn($o) => intval(CardCost($o->CardID)) <= $maxCost,
        'question' => "Exhaust_a_unit_costing_the_same_or_less?", 'prompt' => "Choose_a_unit_to_exhaust",
    ]);
};
