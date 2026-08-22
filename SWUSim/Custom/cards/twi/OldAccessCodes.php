<?php
// TWI_168
// Cost 1 - Old Access Codes - [Aggression] - Upgrade Power 1 - Upgrade HP 0
// Text: When Played: If an opponent controls more units than you, draw a card.

// TWI_168 Old Access Codes — "When Played: If an opponent controls more units than you, draw a card."
// (Upgrade; any friendly host.)
$whenPlayedAbilities["TWI_168:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "AN opponent" in a CONDITION is EXISTENTIAL — true if ANY live opponent qualifies — not a target
    // to be chosen. This card must therefore NEVER prompt for a seat; adding a picker would be its own
    // I1 violation (a prompt Premier must never see). OtherPlayer() interrogated exactly one seat, so
    // above two seats two of the three opponents were invisible to the test, and from seats 2/3/4 only
    // seat 1 was ever compared. OpponentsOf() also filters to LIVE seats, so an eliminated seat's
    // abandoned board cannot satisfy the condition.
    // ⚠ THE PLAUSIBLE WRONG FIX IS A SUM. "an opponent controls more units than you" is an OR across
    // opponents, never a total across the table — three opponents with one unit each must NOT satisfy it.
    $mine = count(GetUnitsInPlay(intval($player)));
    foreach (OpponentsOf(intval($player)) as $o) {
        if (count(GetUnitsInPlay($o)) > $mine) { DoDrawCard(intval($player), 1); break; }
    }
};
