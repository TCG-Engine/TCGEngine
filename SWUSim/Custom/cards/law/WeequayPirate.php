<?php
// LAW_231
// Cost 2 - Weequay Pirate - [Cunning] - Power 2 - HP 3
// Text: Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.) / When Played: If no resources were paid to play this unit, give an Experience token to it.

// ── Task 3.2: LAW_231 Weequay Pirate ────────────────────────────────────────
// "When Played: If no resources were paid to play this unit, give an Experience token to it."
// SWUUnitResourcesPaid reads the SWU_PAID_n TurnEffect stamped by ActivateCard (Task 3.1).
// Returns 0 if no stamp is present (absent = 0 resources paid).
$whenPlayedAbilities["LAW_231:0"] = function($player, $mzID) {
    global $playerID;
    $playerID = intval($player);
    $obj = GetZoneObject($mzID);
    if (SWUObjGone($obj)) return;
    if (SWUUnitResourcesPaid($obj) === 0) {
        DoGiveExperienceToken(intval($player), $mzID);
    }
};
