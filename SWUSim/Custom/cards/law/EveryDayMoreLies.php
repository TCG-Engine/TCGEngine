<?php
// LAW_204
// Every Day, More Lies
// Text: Each player discards a card from their hand.

// "Each player discards a card from their hand." — hidden information, so CR v9.0 7.1.a: every live seat chooses
// independently (caster first in the queue order), and the picks are discarded together once the last seat has
// answered. The just-played event still sits in the caster's hand, so one copy of it is excluded from the
// caster's pool. See SWUEachSeatDiscardsSimultaneously (CardHelpers.php).
$whenPlayedAbilities["LAW_204:0"] = function($player, $mzID = '') {
    SWUEachSeatDiscardsSimultaneously(intval($player), SWUSeatsInPlayerOrder(intval($player)), 'discard', 1,
        "Discard_a_card_from_your_hand", 'LAW_204');
};
