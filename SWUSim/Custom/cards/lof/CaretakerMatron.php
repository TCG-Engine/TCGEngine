<?php
// LOF_243
// Cost 2 - Caretaker Matron - [Heroism] - Power 0 - HP 4
// Text: Action [Exhaust]: If you played a Force card this phase, draw a card.

// LOF_243 Caretaker Matron — Action [Exhaust]: if you played a Force card this phase, draw a card.
$unitAbilities["LOF_243"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_PLAYED_FORCE_CARD') > 0) DoDrawCard(intval($player), 1);
    SWUAfterAction($player);
};
