<?php
// SEC_075
// Knowledge and Defense
// Text: Give a unit -2/-2 for this phase. Draw a card.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_075:0"] = function($player, $mzID = '') {
// Knowledge and Defense — Give a unit -2/-2 for this phase. Draw a card.
            DoDrawCard(intval($player), 1);                 // "Draw a card" is unconditional
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'APPLY_PHASE_DEBUFF|2|2|', 'side' => 'any',
                'prompt' => "Give_a_unit_-2/-2_for_this_phase",
            ]);
};
