<?php
// LOF_100
// Cost 7 - Kelleran Beq - The Sabered Hand - [Command,Heroism] - Power 7 - HP 7
// Text: When Played: Search the top 7 cards of your deck for a unit, reveal it, and play it. It costs 3 resources less. (Put the other cards on the bottom of your deck in a random order.)

// LOF_100 Kelleran Beq — When Played: search the top 7 for a unit, reveal it, and play it costing 3 less.
// Only offer units the player can actually pay for at the discounted price — otherwise the UI lets you
// pick an unaffordable unit and the play just fizzles at resolve. Affordability mirrors the resolve
// formula exactly: max(0, cost + aspect penalty − 3) ≤ ready resources (counted now, AFTER Kelleran's
// own cost was paid on the way into play).
$whenPlayedAbilities["LOF_100:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $ready = SWUTotalPaymentCapacity(intval($player));
    _topDeckSearchBegin(intval($player), 7,
        fn($c) => strpos(CardType($c) ?? '', 'Unit') !== false
                  && max(0, intval(CardCost($c)) + SWUAspectPenalty(intval($player), $c) - 3) <= $ready,
        "count:1", "LOF_100#0");
};

$customDQHandlers["LOF_100#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $allIDs   = array_values(array_filter(explode(',', $parts[0] ?? '')));
    $resolved = _topDeckResolveFromIDs($allIDs, $lastDecision ?? '');
    $back = [];
    foreach ($resolved['drawn'] as $cardID) {
        if (SWUCardPlayBlocked(intval($player), $cardID)) { $back[] = $cardID; continue; }
        $cost = max(0, intval(CardCost($cardID)) + SWUAspectPenalty(intval($player), $cardID) - 3);
        if ($cost > 0 && !SWUPayInlineAbilityCost(intval($player), $cost)) { $back[] = $cardID; continue; } // unaffordable → not played
        $uid = NextUniqueID();
        if (CardArena($cardID) === 'Space') {
            AddSpaceArena($player, CardID: $cardID, Status: 0, Owner: $player, Controller: $player, UniqueID: $uid);
        } else {
            AddGroundArena($player, CardID: $cardID, Status: 0, Owner: $player, Controller: $player, UniqueID: $uid);
        }
        AddGlobalEffects(intval($player), 'SWU_CARDS_PLAYED');
    }
    _topDeckPutRemainingToBottom(intval($player), array_merge($resolved['remaining'], $back));
};
