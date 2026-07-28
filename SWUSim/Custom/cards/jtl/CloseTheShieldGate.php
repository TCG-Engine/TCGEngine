<?php
// JTL_074
// Cost 1 - Close the Shield Gate - [Vigilance]
// Text: Choose a base. The next time damage would be dealt to it this phase, prevent that damage.

// ── JTL_074 Close the Shield Gate — arm the one-shot base-damage prevention on the chosen base's owner.
$customDQHandlers["JTL_074#0"] = function($player, $parts, $lastDecision) {
    if (SWUDecisionDeclined($lastDecision)) return;
    $owner = SWUMzOwner((string)$lastDecision, intval($player));   // Twin Suns: my/their/p{n} → owner seat
    AddGlobalEffects($owner, 'SWU_SHIELD_GATE');
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_074:0"] = function($player, $mzID = '') {
// Close the Shield Gate — "Choose a base. The next time damage would be dealt to
                          // it this phase, prevent that damage." Arm the SWU_SHIELD_GATE flag on the
                          // chosen base's owner (consumed in SWUDealDamageToBase, cleared at regroup).
            global $playerID;
            $playerID = intval($player);
            SWUQueueChooseTarget(intval($player), ["myBase-0", "theirBase-0"],
                "Choose_a_base_to_protect_from_the_next_damage", "JTL_074#0");
            return;
};
