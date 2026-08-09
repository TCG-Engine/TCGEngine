<?php
// TS26_62
// Cost 2 - R2-D2 - Getting His Chance - [Aggression,Heroism] - Power 1 - HP 3
// Text: Raid 2 (This unit gets +2/+0 while attacking.) / When Played: You may deal 2 damage to a base. If you do, that base's controller draws a card.

// TS26_62 R2-D2 — Raid 2 (auto). When Played: you may deal 2 damage to a base. If you do, that base's
// controller draws a card.
$whenPlayedAbilities["TS26_62:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUQueueMayChooseTarget(intval($player), ['myBase-0', 'theirBase-0'], "Deal_2_damage_to_a_base?", "Choose_a_base", "TS26_62#0");
};

$customDQHandlers["TS26_62#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision) || !str_contains($lastDecision, '-')) return;
    $bp = ($lastDecision === 'myBase-0') ? intval($player) : OtherPlayer(intval($player));
    // "…deal 2 damage to a base. IF YOU DO, that base's controller draws a card." The draw is gated on
    // damage ACTUALLY landing — JTL_074 Close the Shield Gate (or any other prevention on that base)
    // stops it, and then nobody draws. Sample the base before and after rather than assuming it stuck.
    $dmgOf = function(int $seat): int {
        foreach (GetBase($seat) as $b) { if (empty($b->removed)) return intval($b->Damage ?? 0); }
        return 0;
    };
    $before = $dmgOf($bp);
    SWUDealDamageToBase(2, $bp);
    if ($dmgOf($bp) > $before) DoDrawCard($bp, 1);   // that base's controller draws
};
