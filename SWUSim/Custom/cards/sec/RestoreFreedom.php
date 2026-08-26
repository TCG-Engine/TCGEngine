<?php
// SEC_257
// Restore Freedom
// Text: Play a unit from your hand. It costs 1 resource less for each Heroism aspect icon among friendly units.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_257:0"] = function($player, $mzID = '') {
// Restore Freedom — Play a unit from your hand. It costs 1 resource less for
                          // each Heroism aspect icon among friendly units.
            global $playerID; $playerID = intval($player);
            $disc = 0;
            foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
                if (!empty($u->removed)) continue;
                foreach (SWUCardAspectIcons($u->CardID ?? '') as $a) { if ($a === 'Heroism') $disc++; }
            }
            SWUOfferDiscountPlay(intval($player), ['discount'=>$disc, 'types'=>['Unit'], 'afterAction'=>false,
                'prompt'=>"Play_a_unit_(1_less_per_Heroism_icon_among_friendly_units)"]);
};
