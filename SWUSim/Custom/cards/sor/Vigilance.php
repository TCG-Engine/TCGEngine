<?php
// SOR_058
// Vigilance
// Text: Choose two, in any order:  Discard 6 cards from an opponent's deck. Heal 5 damage from a base. Defeat a unit with 3 or less remaining HP. Give a Shield token to a unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_058:0"] = function($player, $mzID = '') {
// Vigilance — "Choose two, in any order: Discard 6 from an opponent's deck /
                          // Heal 5 from a base / Defeat a unit with ≤3 remaining HP / Give a Shield to a
                          // unit." Sequential OPTIONCHOOSE driver (see SWUQueueModalChoose).
            SWUQueueModalChoose(intval($player), 'SOR_058', ['Discard6', 'Heal5', 'Defeat', 'Shield'], 2);
            return;
};
