<?php
// IC27_117
// Cost 3 - Hera Syndulla - Against the Odds - [Aggression,Heroism] - Unit (Ground) 4/3 (unique)
//   Traits: Rebel, Twi'lek, Spectre
// Text: When you play another Rebel card: Deal 1 damage to a base.
//
// No registrations — the reaction is a case in SWUCollectOwnPlayReactions (GameLogic.php) beside SOR_143 Fighters
// for Freedom, the same shape with a trait gate instead of an aspect one: "another" by UniqueID (her own play does
// not count), the Rebel trait read off the PLAYED card of any type (a Rebel pilot played as an upgrade counts),
// only for plays by her controller, not while blanked. The trigger is dispatched in DispatchTrigger; the base pick
// is mandatory (no "may") and spans every base at the table, her controller's own included.
// Tests: SWUSim/Tests/Cases/ic27/HeraSyndulla_AgainstTheOdds.md
