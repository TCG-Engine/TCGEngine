<?php
// SHD_145
// Headhunting
// Text: Attack with up to 3 units (one at a time). They can't attack bases for these attacks. Each Bounty Hunter that attacks this way gets +2/+0 for its attack.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SHD_145:0"] = function($player, $mzID = '') {
// Headhunting — "Attack with up to 3 units (one at a time). They can't attack
                          // bases for these attacks. Each Bounty Hunter that attacks this way gets +2/+0
                          // for its attack." Count-capped attack loop (SWU_SHD145_LOOP); see _SWUShd145Offer.
            global $playerID; $playerID = intval($player);
            SetSWUVar('SWU_SHD145_LOOP', '3');   // up to 3 attacks, no attackers excluded yet (comma-CSV)
            _SWUShd145Offer(intval($player));
            return;
};
