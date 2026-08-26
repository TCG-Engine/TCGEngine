<?php
// TWI_186
// Cost 6 - San Hill - Chairman of the Banking Clan - [Cunning,Villainy] - Power 3 - HP 7
// Text: Exploit 3 (While playing this card, defeat up to 3 units you control. This card costs 2 resources less for each unit defeated this way.) / On Attack: For each friendly unit that was defeated this phase, ready a friendly resource.

// TWI_186 San Hill — "Exploit 3. On Attack: For each friendly unit that was defeated this phase, ready
// a friendly resource." (SWU_FRIENDLY_DEFEATED is the count-based per-phase friendly-defeat flag.)
$onAttackAbilities["TWI_186:0"] = function($player, $mzID) {
    $n = GlobalEffectCount(intval($player), 'SWU_FRIENDLY_DEFEATED');
    // "ready a friendly resource" per defeated friendly unit — team-wide, player picks the split
    // (same seam as SEC_225 Synara San / SHD_221 Wanted).
    if ($n > 0) SWUReadyFriendlyResources(intval($player), $n);
    // Combat owns the after-action.
};
