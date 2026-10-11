<?php
// TWI_238
// Merciless Contest
// Text: Each player chooses a non-leader unit they control. Defeat those units.

// Merciless Contest — "Each player chooses a non-leader unit they control. Defeat those units."
// CR v9.0 7.1.a: a choice about OPEN information is made by each player in turn, "then resolve the ability
// simultaneously". So every seat picks first (caster first), and only then are all the picks defeated — the old
// chain defeated the caster's own pick the moment it was made, so its When Defeated could resolve (and change the
// board) before the opponents had chosen. Same chain as LAW_099 Governor's Shuttle, with the non-leader filter.
// 'my' units only: the text is "a non-leader unit THEY CONTROL" — a Team Suns teammate's unit is friendly but not
// yours to pick.
$whenPlayedAbilities["TWI_238:0"] = function($player, $mzID = '') {
    SWUEachPlayerChoosesOwnUnitToDefeat(intval($player), true, "Choose_your_non-leader_unit_to_defeat", true);
};
