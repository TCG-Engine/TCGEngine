<?php
// TWI_134
// Cost 4 - Asajj Ventress - Count Dooku's Assassin - [Aggression,Villainy] - Power 2 - HP 4
// Text: Exploit 2 (While playing this card, defeat up to 2 units you control. This card costs 2 resources less for each unit defeated this way.) / On Attack: If you've attacked with another Separatist unit this phase, this unit gets +3/+0 for this phase.

// ── TWI Phase 3 — Exploit payloads (the Exploit keyword itself is generic; these are the riders) ──
// TWI_134 Asajj Ventress — "On Attack: If you've attacked with another Separatist unit this phase, this
// unit gets +3/+0 for this phase." The count-based SWU_ATTACKED_SEPARATIST includes this attack, so
// "another" = count ≥ 2.
$onAttackAbilities["TWI_134:0"] = function($player, $mzID) {
    if (GlobalEffectCount(intval($player), 'SWU_ATTACKED_SEPARATIST') >= 2) {
        SWUApplyPhaseBuff($mzID, 3, 0, 'TWI_134');
    }
    // Combat owns the after-action.
};
