<?php
// JTL_078
// Direct Hit
// Text: Defeat a non-leader Vehicle unit.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["JTL_078:0"] = function($player, $mzID = '') {
// Direct Hit — defeat a non-leader Vehicle unit.
            SWUOfferUnitTarget($player, $mzID, [
                'continuation' => 'DEFEAT_UNIT', 'nonLeader' => true, 'traits' => ['Vehicle'],
                'prompt' => "Defeat_a_non-leader_Vehicle_unit",
            ]);
};
