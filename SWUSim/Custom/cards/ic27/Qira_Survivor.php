<?php
// IC27_045
// Cost 3 - Qi'ra - Survivor - [Vigilance,Vigilance] - Unit (Ground) 1/1 (unique)
//   Traits: Underworld
// Text: When Played: Give 2 Experience tokens to this unit.
//       When Defeated: Choose a unit. You may give an Experience token to it for each Experience token that was
//       on this unit.
//
// When Defeated: "each Experience token that WAS on this unit" is last-known information. By the time the
// closure runs her subcards are gone, so CollectWhenDefeatedTriggers calls _SWUIc27045Snapshot in the same window
// the JTL_104 Raddus power snapshot uses (subcards still attached) and persists the count in a SWUVar keyed by her
// mzID, so a later request (a trigger-order prompt, a JTL_002 Thrawn / JTL_169 Shadow Caster reuse) still has it.
// Only Experience counts (_CountExperienceSubcards — a Shield is not). "Choose a unit. You may give …" is one
// optional pick over the whole table (MZMAYCHOOSE); with 0 tokens there is nothing to give, so nothing is offered.
// Tests: SWUSim/Tests/Cases/ic27/Qira_Survivor.md
$whenPlayedAbilities["IC27_045:0"] = function($player, $mzID) {
    DoGiveExperienceToken(intval($player), $mzID);
    DoGiveExperienceToken(intval($player), $mzID);
};

function _SWUIc27045Snapshot(string $mzID): void {
    $o = GetZoneObject($mzID);
    if ($o === null) return;
    SetSWUVar('SWU_IC27045_EXP_' . str_replace('-', '_', $mzID), strval(_CountExperienceSubcards($o)));
}

$whenDefeatedAbilities["IC27_045:0"] = function($player, $mzID) {
    $n = intval(GetSWUVar('SWU_IC27045_EXP_' . str_replace('-', '_', (string)$mzID), '0'));
    if ($n <= 0) return;
    SWUOfferUnitTarget(intval($player), '', ['may' => true, 'continuation' => "GIVE_EXPERIENCE|{$n}",
        'prompt' => "Give_{$n}_Experience_token(s)_to_a_unit"]);
};
