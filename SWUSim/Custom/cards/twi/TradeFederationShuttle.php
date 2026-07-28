<?php
// TWI_060
// Cost 3 - Trade Federation Shuttle - [Vigilance] - Power 2 - HP 3
// Text: When Played: If you control a damaged unit, create a Battle Droid token.

// TWI_060 Trade Federation Shuttle — "When Played: If you control a damaged unit, create a Battle Droid token."
$whenPlayedAbilities["TWI_060:0"] = function($player, $mzID) {
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && intval($u->Damage ?? 0) > 0) { SWUCreateUnitToken(intval($player), 'TWI_T01'); return; }
    }
};
