<?php
// TWI_083
// Cost 4 - General's Guardian - [Command,Villainy] - Power 4 - HP 4
// Text: When this unit is attacked: Create a Battle Droid token.

// TWI_083 General's Guardian — "When this unit is attacked: Create a Battle Droid token." (On Defense.)
$onDefenseAbilities["TWI_083:0"] = function($player, $mzID) {
    SWUCreateUnitToken(intval($player), 'TWI_T01');
};
