<?php
// SOR_163
// Cost 3 - Star Wing Scout - [Aggression] - Power 4 - HP 1
// Text: When Defeated: If you have the initiative, draw 2 cards.

// SOR_163 Star Wing Scout — When Defeated: If you have the initiative, draw 2 cards.
$whenDefeatedAbilities["SOR_163:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    // ⚠ "If YOU have the initiative" — PlayerHasIniative matches "P{seat}_CLAIMED"/"P{seat}_UNCLAIMED"
    // for ANY seat. The old `strpos($ic,'P1') === 0 ? 1 : 2` collapsed every non-P1 holder onto seat 2,
    // so above two seats a seat-3/4 holder never drew and seat 2 drew when it should not have.
    // (Engine spells it "Iniative" — a load-bearing typo.)
    if (PlayerHasIniative(intval($player))) DoDrawCard(intval($player), 2);
};
