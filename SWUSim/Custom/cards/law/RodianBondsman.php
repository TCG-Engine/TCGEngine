<?php
// LAW_116
// Cost 2 - Rodian Bondsman - [Vigilance] - Power 2 - HP 3
// Text: When Defeated: Each player creates a Credit token.

// LAW_116 Rodian Bondsman — When Defeated: each player creates a Credit token.
$whenDefeatedAbilities["LAW_116:0"] = function($player, $mzID) {
    // "EACH player" — every live seat, not just the two. Was two explicit calls.
    foreach (SWUSeatsInPlayerOrder(intval($player)) as $p) {
        SWUCreateCreditToken($p, 1);
    }
};
