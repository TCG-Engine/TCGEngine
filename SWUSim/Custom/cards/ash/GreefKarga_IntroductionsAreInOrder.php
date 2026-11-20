<?php
// ASH_119
// Cost 2 - Greef Karga - Introductions are in Order - [Command] - Power 2 - HP 3
// Text: Action [1 resource, Exhaust]: If your base was attacked this phase, create a Mandalorian token.

// ASH_119 Greef Karga — Action [1 resource, Exhaust]: if your base was attacked this phase, create a
// Mandalorian token. The base-attacked clause is RESOLUTION, not cost, so the Action is usable either way (CR
// 6.4.587.c, owner go 2026-11-04) — it pays and creates nothing when the base was not attacked. Registered
// AFTER the $unitAbilities=[] init so it isn't wiped.
$unitActionResourceCosts["ASH_119"] = 1;

$unitAbilities["ASH_119"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (GlobalEffectCount(intval($player), 'SWU_BASE_ATTACKED') > 0) SWUCreateUnitToken(intval($player), 'ASH_T01');
    SWUAfterAction(intval($player));
};
