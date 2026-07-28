<?php
// SHD_216
// Cost 4 - Chain Code Collector - [Cunning] - Power 4 - HP 2
// Text: Ambush (When you play this unit, it may ready and attack an enemy unit.) / On Attack: If the defender has a Bounty, it gets -4/-0 for this attack.

// ─── SHD_216 Chain Code Collector ─────────────────────────────────────────────
// Ambush (auto) + On Attack: if the defender has a Bounty, it gets -4/-0 for this attack. The real effect
// is applied synchronously in ExecuteSWUAttack (SWU_DEF_DEBUFF_4); this stub handler is a no-op.
$onAttackAbilities["SHD_216:0"] = function($player, $mzID) { /* effect applied in ExecuteSWUAttack */ };
