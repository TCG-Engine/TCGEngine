<?php
// SHD_115
// Cost 3 - Cobb Vanth - The Marshal - [Command] - Power 3 - HP 2
// Text: When Defeated: Search the top 10 cards of your deck for a unit that costs 2 or less and discard it. For this phase, you may play that card from your discard pile for free.

// ─── SHD_115 (When Defeated) ──────────────────────────────────────────────────
// When Defeated: Search the top 10 cards of your deck for a unit that costs 2 or less and discard it.
// For this phase, you may play that card from your discard pile for free.
$whenDefeatedAbilities["SHD_115:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    _topDeckSearchBegin(intval($player), 10,
        function($cid) { return strpos(CardType($cid) ?? '', 'Unit') !== false && intval(CardCost($cid)) <= 2; },
        "count:1", "SHD_115#0");
};

// Finalize: discard the chosen unit tagged TPF (free play from discard this phase); rest to the bottom.
$customDQHandlers["SHD_115#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    foreach ($resolved['drawn'] as $cardID) {
        SWUAddToDiscard(intval($player), $cardID, 'MILL', 'TPF');
    }
    _topDeckPutRemainingToBottom(intval($player), $resolved['remaining']);
};
