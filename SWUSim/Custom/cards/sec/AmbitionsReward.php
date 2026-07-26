<?php
// SEC_175
// Cost 2 - Ambition's Reward - [Aggression] - Upgrade Power 1 - Upgrade HP 1
// Text: When Played: Create a Spy token.

// SEC_175 Ambition's Reward (Upgrade) — When Played: create a Spy token.
$whenPlayedAbilities["SEC_175:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'SEC_T01');
};
