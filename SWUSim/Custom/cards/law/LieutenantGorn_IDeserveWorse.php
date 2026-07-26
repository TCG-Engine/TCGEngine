<?php
// LAW_221
// Cost 3 - Lieutenant Gorn - I Deserve Worse - [Cunning,Heroism] - Power 4 - HP 4
// Text: On Attack: Take control of an enemy Credit token.

// LAW_221 Lieutenant Gorn — On Attack: take control of an enemy Credit token. (Net: opponent loses a
// Credit, you gain one — Credit tokens are fungible.)
$onAttackAbilities["LAW_221:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $enemy = SWUEnemyCreditTokenMzIDs(intval($player));
    if (empty($enemy)) return;
    $o = GetZoneObject($enemy[0]);
    if (SWUObjGone($o)) return;
    $o->removed = true;
    DecisionQueueController::CleanupRemovedCards();
    SWUCreateCreditToken(intval($player), 1);
};
