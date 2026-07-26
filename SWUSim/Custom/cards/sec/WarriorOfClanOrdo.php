<?php
// SEC_164
// Cost 2 - Warrior of Clan Ordo - [Aggression] - Power 3 - HP 3
// Text: On Attack: You may disclose Aggression (reveal a card from your hand with this aspect icon). If you don't, deal 2 damage to your base.

// SEC_164 Warrior of Clan Ordo — On Attack: you may disclose Aggression. If you DON'T, deal 2 to your base.
$onAttackAbilities["SEC_164:0"] = function($player, $mzID) {
    SWUQueueDisclose(intval($player), ['Aggression'], "",
        "Disclose_Aggression_or_take_2_to_your_base", "SEC_164#0");
};

$customDQHandlers["SEC_164#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    // Pass the DEALER explicitly ($player). For self-base damage the inference would otherwise fall back
    // to the opponent, mis-attributing it — which suppresses "when YOU deal non-combat damage" reactions
    // (JTL_009 Boba Fett) for the actual dealer. With $player as damager, Boba (owned by $player) fires.
    SWUDealDamageToBase(2, intval($player), intval($player));   // "if you don't" penalty to own base
};
