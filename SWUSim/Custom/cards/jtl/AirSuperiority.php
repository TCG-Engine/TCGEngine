<?php
// JTL_125
// Air Superiority
// Text: If you control more space units than an opponent, deal 4 damage to a ground unit that opponent controls.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_125:0"] = function($player, $mzID = '') {
// Air Superiority — "If you control more space units than an opponent, deal 4
                          // damage to a ground unit that opponent controls."
            global $playerID;
            $playerID = intval($player);
            $opp = OtherPlayer(intval($player));
            $mine = 0; foreach (GetSpaceArena(intval($player)) as $u) { if (empty($u->removed)) $mine++; }
            $thrs = 0; foreach (GetSpaceArena($opp)             as $u) { if (empty($u->removed)) $thrs++; }
            if ($mine <= $thrs) return;
            SWUOfferUnitTarget(intval($player), '', [
                'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 4, 'side' => 'their', 'arena' => 'Ground',
                'prompt' => "Deal_4_to_an_enemy_ground_unit",
            ]);
            return;
};
