<?php
// IC27_187
// Cost 2 - Jar Jar Binks - Bumbling Representative - [Heroism] - Unit (Ground) 1/5 (unique)
//   Traits: Republic, Gungan, Official
// Text: On Attack: Discard a card from your deck. If it costs 6 or more, this unit gets +4/+0 for
//       this attack.

// The discard is UNCONDITIONAL — only the buff is gated on the cost, so a cheap top card still mills.
// SWUMillTopCard returns the milled CardID (null on an empty deck, where the whole ability no-ops).
// "+4/+0 for THIS ATTACK" is SWUAddAttackPowerBonus (a one-shot SWU_ATK_POWER_4 marker consumed by
// SWUCombatDamage), NOT SWUApplyPhaseBuff — a phase buff would linger past the attack.
// No decisions are queued here, so this is safe directly in the On Attack closure (the mandatory-
// MZCHOOSE $playerID-restore trap does not apply); combat owns the After Action.
$onAttackAbilities["IC27_187:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $milled = SWUMillTopCard(intval($player));
    if ($milled === null) return;                                  // empty deck — nothing discarded, no buff
    if (intval(CardCost($milled)) >= 6) SWUAddAttackPowerBonus($mzID, 4);
};
