<?php
// SEC_115
// Cost 3 - Taylander Shuttle - [Command] - Power 2 - HP 4
// Text: On Attack: If you have the initiative, create a Spy token.

// SEC_115 Taylander Shuttle — On Attack: if you have the initiative, create a Spy token.
$onAttackAbilities["SEC_115:0"] = function($player, $mzID) {
    if (PlayerHasIniative(intval($player))) SWUCreateUnitToken(intval($player), 'SEC_T01');
};
