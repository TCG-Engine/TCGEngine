<?php
// IC27_116
// Cost 3 - Black One - Draw Fire, Twice - [Aggression,Heroism] - Unit (Space) 2/2 (unique)
//   Traits: Resistance, Vehicle, Fighter
// Text: When Played/When Defeated: Draw a card.
//
// One closure on both triggers. The resolving player draws: the player who played it, or its controller when it
// was defeated (a stolen copy draws for the thief; the card itself goes to its owner's discard).
// Tests: SWUSim/Tests/Cases/ic27/BlackOne_DrawFireTwice.md
$whenPlayedAbilities["IC27_116:0"] =
$whenDefeatedAbilities["IC27_116:0"] = function($player, $mzID) {
    DoDrawCard(intval($player), 1);
};
