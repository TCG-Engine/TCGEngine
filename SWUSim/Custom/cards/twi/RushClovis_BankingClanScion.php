<?php
// TWI_183
// Cost 4 - Rush Clovis - Banking Clan Scion - [Cunning,Villainy] - Power 3 - HP 5
// Text: Raid 2 / On Attack: If the defending player controls no ready resources, create a Battle Droid token.

// TWI_183 Rush Clovis — Raid 2 + "On Attack: If the defending player controls no ready resources, create
// a Battle Droid token."
$onAttackAbilities["TWI_183:0"] = function($player, $mzID) {
    // "If THE DEFENDING PLAYER controls no ready resources" — a determined seat, not OtherPlayer().
    if (SWUResourceCount(SWUCurrentDefendingSeat(intval($player)), true) === 0) SWUCreateUnitToken(intval($player), 'TWI_T01');
    // Combat owns the after-action.
};
