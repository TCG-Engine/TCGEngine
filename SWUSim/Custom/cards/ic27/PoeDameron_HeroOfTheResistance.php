<?php
// IC27_115
// Cost 2 - Poe Dameron - Hero of the Resistance - [Aggression,Heroism] - Unit (Ground) 2/4 (unique)
//   Traits: Resistance
// Text: When you play another Resistance card: This unit gets +1/+0 for this phase.
//
// No registrations — the reaction is a case in SWUCollectOwnPlayReactions (GameLogic.php) beside SOR_143 Fighters for
// Freedom: "another" by UniqueID (his own play does not count), the Resistance trait read off the PLAYED card of any
// type (a Resistance pilot played as an upgrade counts), only for plays by his controller, not while blanked. The
// +1/+0 is a phase STAT_BUFF with a unique ^IC27115_<n> suffix so each play stacks.
// Tests: SWUSim/Tests/Cases/ic27/PoeDameron_HeroOfTheResistance.md
