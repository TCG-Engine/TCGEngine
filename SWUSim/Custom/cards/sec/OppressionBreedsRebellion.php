<?php
// SEC_158
// Oppression Breeds Rebellion
// Text: If a friendly unit was defeated while attacking this phase, draw 3 cards.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_158:0"] = function($player, $mzID = '') {
// Oppression Breeds Rebellion — if a friendly unit was defeated WHILE ATTACKING
                          // this phase, draw 3 cards.
            global $playerID; $playerID = intval($player);
            if (GlobalEffectCount(intval($player), 'SWU_ATTACKER_DEFEATED') > 0) DoDrawCard(intval($player), 3);
            return;
};
