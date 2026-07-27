<?php
// SEC_077
// Retaliation
// Text: Defeat a unit that dealt damage to a base this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SEC_077:0"] = function($player, $mzID = '') {
// Retaliation — "Defeat a unit that dealt damage to a base this phase."
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'DEFEAT_UNIT',
                'extraFilter' => fn($o) => GlobalEffectCount(intval($o->Controller ?? 0), 'SWU_DEALT_BASEDMG_' . intval($o->UniqueID ?? 0)) > 0,
                'prompt' => "Defeat_a_unit_that_damaged_a_base_this_phase",
            ]);
};
