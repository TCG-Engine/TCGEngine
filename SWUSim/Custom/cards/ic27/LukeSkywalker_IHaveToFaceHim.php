<?php
// IC27_006
// Cost 6 - Luke Skywalker - I Have to Face Him - [Aggression,Heroism] - Leader 4/6 (unique)
//   Traits: Force, Jedi, Rebel
// Text (leader): Draw 2 more cards in your starting hand.
//                Epic Action: If you control 6 or more resources, deploy this leader.
// Text (deployed): Overwhelm. On Attack: You may discard a card from your hand. If you do, this unit gets +2/+0
//                  for this attack.
//
// FRONT: a pregame modifier with no ability window — it is the leader entry in SWUStartingHandSizeFor()
// (GameLogic.php), which the opening draw, the mulligan redraw and the test harness all read. Per the official
// Colossus ruling (its "-1" also applies to the mulligan hand), the +2 applies to the mulligan hand too.
// Epic deploy is generic (printed cost 6). Overwhelm is in $Overwhelm_Cards (from the deployed text).
// DEPLOYED On Attack: ASH_172 Razor Crest's sentence word for word — dispatched to its closure at CALL time, so
// the two cards cannot drift and the per-card load order does not matter.
$onAttackAbilities["IC27_006:0"] = function($player, $mzID) {
    global $onAttackAbilities;
    $onAttackAbilities["ASH_172:0"]($player, $mzID);
};
