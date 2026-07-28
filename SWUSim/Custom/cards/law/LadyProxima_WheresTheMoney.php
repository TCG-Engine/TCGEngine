<?php
// LAW_235
// Cost 3 - Lady Proxima - Where's the Money? - [Cunning] - Power 1 - HP 5
// Text: Action [Exhaust]: Create a Credit token.

// LAW_235 Lady Proxima — "Action [Exhaust]: Create a Credit token." Default 'exhaust' cost kind (the
// framework exhausts her and requires her ready); no resource cost, no extra affordability gate.
$unitAbilities["LAW_235"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUCreateCreditToken(intval($player), 1);
    SWUAfterAction($player);
};
