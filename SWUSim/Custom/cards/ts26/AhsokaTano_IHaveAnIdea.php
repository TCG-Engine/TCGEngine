<?php
// TS26_08
// Cost 5 - Ahsoka Tano - I Have an Idea - [Cunning,Heroism] - Power 3 - HP 6
// Text: When you play an event: You may exhaust this leader. If you do, look at the top card of your deck. You may play it (paying its cost), discard it, or leave it on top of your deck.
// DeployText: Raid 1 (This unit gets +1/+0 while attacking.) / When Attack Ends: Look at the top card of your deck. You may play it, discard it, or leave it on top of your deck. If you play it, it costs 1 resource less.
// Epic Action: If you control 5 or more resources, deploy this leader.

// TS26_08 Ahsoka Tano (front) — reactive "when you play an event": may exhaust this leader → look at the
// top card, then play it (full cost) / discard / leave. Hooked from OnPlayEvent (undeployed-leader
// observer). Deployed side: Raid 1 auto + When Attack Ends look-top (play at -1).
$customDQHandlers["TS26_08#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    foreach (GetLeader(intval($player)) as $l) { if (($l->CardID ?? '') === 'TS26_08') { $l->Ready = false; break; } }
    $topIdx = _SWUTopDeckFrontIdx(intval($player));
    if ($topIdx === -1) return;
    $topID = GetDeck(intval($player))[$topIdx]->CardID;
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@{$topID}&Play&Discard&Leave", 1, "Play_the_top_card,_discard_it,_or_leave_it_on_top");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_08#1|0", 1);   // full cost
};

$onAttackEndAbilities["TS26_08:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $topIdx = _SWUTopDeckFrontIdx(intval($player));
    if ($topIdx === -1) return;
    $topID = GetDeck(intval($player))[$topIdx]->CardID;
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@{$topID}&Play&Discard&Leave", 1, "Play_the_top_card_(-1),_discard,_or_leave");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "TS26_08#1|1", 1);   // -1
};

$customDQHandlers["TS26_08#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $disc = intval($parts[0] ?? 0);
    if ($lastDecision === 'Discard') SWUMillTopCard(intval($player));
    elseif ($lastDecision === 'Play') SWUPlayTopDeckCard(intval($player), false, $disc);
};
