<?php
// IC27_163
// Cost 7 - Bo-Katan Kryze - Right Place, Right Time - [Cunning,Heroism] - Unit (Ground) 7/6 (unique)
//   Traits: Mandalorian
// Text: Shielded. When Played: Give each enemy unit -7/-0 for this phase.
//
// Shielded is printed (auto-wired). The When Played is a SNAPSHOT of the enemy units in play as it resolves
// (a unit that enters later this phase is not affected): each gets the registered IC27_163 STAT_DEBUFF (-7/-0,
// phase duration, expired centrally at regroup). "enemy" = 'their', which is the opponent fan-out — every other
// seat in Twin Suns, never a teammate in Team Suns. -0 HP, so no shrink sweep is needed.
// Tests: SWUSim/Tests/Cases/ic27/BoKatanKryze_RightPlaceRightTime.md
$whenPlayedAbilities["IC27_163:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (_SWUCollectUnitTargets(intval($player), ['side' => 'their']) as $mz) {
        SWUApplyPhaseDebuff($mz, 7, 0, 'IC27_163');
    }
};
