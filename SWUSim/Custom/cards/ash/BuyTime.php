<?php
// ASH_091
// Cost 3 - Buy Time - [Vigilance]
// Text: Create a Mandalorian token and give it Sentinel for this phase.

$whenPlayedAbilities["ASH_091:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $uid = SWUCreateUnitToken(intval($player), 'ASH_T01');
    $mz  = SWUFindMzByUID($uid);
    if ($mz !== null) AddTurnEffect($mz, SWUMakeTurnEffect('SENTINEL', [], SWU_DUR_PHASE, 'ASH_091'));
};
