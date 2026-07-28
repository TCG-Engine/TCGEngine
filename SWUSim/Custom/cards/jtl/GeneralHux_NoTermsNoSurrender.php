<?php
// JTL_134
// Cost 2 - General Hux - No Terms, No Surrender - [Aggression,Villainy] - Power 1 - HP 4
// Text: Each other friendly First Order unit gains Raid 1. (They get +1/+0 while attacking.) / Action [Exhaust]: If you played a First Order card this phase, draw a card.

// JTL_134 General Hux — Action [Exhaust]: If you played a First Order card this phase, draw a card.
// (SWU_PLAYED_FO is set in ActivateCard when a First Order card is played; cleared at RegroupPhaseStart.)
$unitAbilities["JTL_134"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_FO') > 0) DoDrawCard(intval($player), 1);
    SWUAfterAction($player);
};
