<?php
// TS26_62
// Cost 2 - R2-D2 - Getting His Chance - [Aggression,Heroism] - Power 1 - HP 3
// Text: Raid 2 (This unit gets +2/+0 while attacking.) / When Played: You may deal 2 damage to a base. If you do, that base's controller draws a card.

// TS26_62 R2-D2 — Raid 2 (auto). When Played: you may deal 2 damage to a base. If you do, that base's
// controller draws a card.
$whenPlayedAbilities["TS26_62:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player), SWUAllBaseMzIDs(intval($player), 'any'), "Deal_2_damage_to_a_base?", "Choose_a_base", "TS26_62#0");
};

$customDQHandlers["TS26_62#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $bp = SWUMzOwner($lastDecision, intval($player));   // "that base's CONTROLLER" — decode from the mzID
    // "…deal 2 damage to a base. IF YOU DO, that base's controller draws a card."
    // ★ JUDGE RULING 2026-09-14: prevented damage still satisfies "If you do" — "you still tried to damage
    // it" (CR 9.2; the Malakili ruling). So JTL_074 Close the Shield Gate on that base stops the 2 but NOT
    // the draw. This used to sample the base and skip the draw on a prevented hit, which was the wrong
    // reading. (The prevented 2 still fires no "when damage is dealt" reaction — that is CR 8.9, elsewhere.)
    SWUDealDamageToBase(2, $bp);
    DoDrawCard($bp, 1);   // that base's controller draws
};
