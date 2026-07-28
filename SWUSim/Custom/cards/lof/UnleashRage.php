<?php
// LOF_173
// Unleash Rage
// Text: Use the Force (lose your Force token). If you do, give a friendly unit +3/+0 for this phase.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LOF_173:0"] = function($player, $mzID = '') {
// Unleash Rage — "Use the Force. If you do, give a friendly unit +3/+0 this phase."
            if (!PlayerHasTheForce(intval($player))) return;
            UseTheForce(intval($player));
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'APPLY_PHASE_BUFF|3|0|LOF_173',
                'side' => 'my', 'prompt' => "Give_a_friendly_unit_+3/+0",
            ]);
};
