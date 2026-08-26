<?php
// HMW_102
// Cost 4 - Dragon's Might - [Vigilance] - Event
// Traits: Innate
// Text: Defeat a non-leader unit with 4 or less power.
//
// The two released halves of this card already exist and this is their intersection:
//   SOR_077 Takedown — "Defeat a unit with 5 or less remaining HP"  (the threshold-filtered defeat)
//   SOR_078 Vanquish — "Defeat a non-leader unit"                   (the leader exclusion)
// so it is the same SWUOfferUnitTarget → DEFEAT_UNIT shape, with the metric changed to POWER.
//
// Three readings worth stating, each with its own guard:
//   • POWER IS CURRENT, not printed. ObjectCurrentPower folds in upgrades and phase buffs/debuffs, so a
//     printed 3/3 wearing Academy Training is out of reach at 5 and a printed 5/5 carrying -1/-0 comes
//     into reach at 4. (Cost would be the printed value; power never is.)
//   • "NON-LEADER" is a live-object question, not a printed CardType one — the nonLeader option runs
//     IsLeaderUnit($obj), so a deployed leader AND a unit made a leader unit by ASH_135 The Darksaber
//     are both excluded.
//   • "a non-leader unit" names no controller and no arena, so the pool is the whole table: friendly
//     units, enemy units, ground, space, and token units.
// Mandatory — no "you may" and no "up to" — so it stays a plain MZCHOOSE with no decline, and simply
// resolves to nothing when the board holds no unit at or under the threshold.

$whenPlayedAbilities["HMW_102:0"] = function($player, $mzID = '') {
    SWUOfferUnitTarget($player, $mzID, [
        'continuation' => 'DEFEAT_UNIT',
        'nonLeader'    => true,
        'extraFilter'  => fn($o) => intval(ObjectCurrentPower($o)) <= 4,
        'prompt'       => "Defeat_a_non-leader_unit_with_4_or_less_power",
    ]);
};
