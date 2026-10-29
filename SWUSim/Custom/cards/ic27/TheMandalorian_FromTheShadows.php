<?php
// IC27_157
// Cost 4 - The Mandalorian - From the Shadows - [Cunning,Heroism] - Unit (Ground) 5/3 (unique)
//   Traits: Mandalorian, Bounty Hunter
// Text: Shielded. On Attack: If the defending unit is exhausted, it gets -4/-0 for this attack.
//
// SEC_208 Hunter's text word for word. Shielded is printed (auto-wired). The -4/-0 is a one-shot
// SWU_DEF_DEBUFF_4 marker on the ATTACKER, set synchronously in ExecuteSWUAttack (CombatLogic.php) — the deferred
// On Attack trigger would be too late, because SWUCombatDamage reads and consumes the marker first. So this
// stub is empty and registered as "effect applied elsewhere" for the game log.
// Tests: SWUSim/Tests/Cases/ic27/TheMandalorian_FromTheShadows.md
$onAttackAbilities["IC27_157:0"] = function($player, $mzID) { /* effect applied in ExecuteSWUAttack */ };
global $swuLogEffectAppliedElsewhere;   // game log: an empty stub is not "had no effect" (see SWULogNoEffectCheck)
$swuLogEffectAppliedElsewhere['IC27_157'] = true;
