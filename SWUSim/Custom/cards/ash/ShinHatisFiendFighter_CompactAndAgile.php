<?php
// ASH_191
// Cost 2 - Shin Hati's Fiend Fighter - Compact and Agile - [Cunning,Villainy] - Power 3 - HP 1
// Text: When Defeated: You may give 2 Advantage tokens to a unit. If this unit wasn't defeated by combat damage, you may give 3 Advantage tokens to that unit instead.

// ── ASH Phase 2 Batch 2.5 ──
// ASH_191 Shin Hati's Fiend Fighter — When Defeated: you may give 2 Advantage tokens to a unit; if this
// unit wasn't defeated by COMBAT damage, give 3 instead. (gCombatDefeatByMz marks combat defeats — same
// signal as ASH_028.)
$whenDefeatedAbilities["ASH_191:0"] = function($player, $mzID) {
    $fromCombat = !empty($GLOBALS['gCombatDefeatByMz'][$mzID] ?? false);
    $n = $fromCombat ? 2 : 3;
    GiveTokenUpgrade($player, '', [
        'token' => 'ADVANTAGE', 'amount' => $n, 'may' => true, 'friendlyOnly' => false,
        'question' => "Give_{$n}_Advantage_tokens_to_a_unit?", 'prompt' => "Choose_a_unit",
    ]);
};
