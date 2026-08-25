<?php
// TS26_80
// Cost 1 - Reveal Intentions - [Cunning]
// Text: Each player reveals their hand. / In player order, each player discards a card from the hand of the player to their right. Then, each player draws a card.

// "IN PLAYER ORDER, each player discards a card from the hand of THE PLAYER TO THEIR RIGHT."
// Right = the next live seat along SeatOrder (USER RULING 2026-08-21, see SWUSeatToTheRight). At two
// seats your right neighbour is simply the opponent, so Premier is unchanged.
//
// ⚠ SEQUENTIAL, not simultaneous: "in player order" means each seat picks against the board as it
// stands, so an earlier discard can take the very card a later seat wanted. The walk therefore applies
// each discard immediately and recomputes the next seat's pool (the LAW_096 shape, not the LAW_099 one).
// ⚠ Each pick is interactive, so the remaining seats ride the continuation Param.

if (!function_exists('_SWUTs26_80Ask')) {
    function _SWUTs26_80Ask(int $caster, array $remaining): void {
        global $playerID;
        while (!empty($remaining)) {
            $seat  = intval(array_shift($remaining));
            $right = SWUSeatToTheRight($seat);
            $playerID = $seat;                       // pool is minted in the DECIDER's frame
            $hand = [];
            foreach (ZoneSearch("theirHand") as $mz) {
                if (SWUMzOwner($mz, $seat) === $right) $hand[] = $mz;   // ONLY the right neighbour's
            }
            if (empty($hand)) continue;              // that neighbour holds nothing
            SWUQueueChooseTarget($seat, $hand, "Discard_a_card_from_the_hand_of_the_player_to_your_right",
                "TS26_80#0|" . implode(',', $remaining));
            return;
        }
        _SWUTs26_80Draw($caster);
    }
}

// "Then, each player draws a card." — after every discard has resolved.
if (!function_exists('_SWUTs26_80Draw')) {
    function _SWUTs26_80Draw(int $caster): void {
        foreach (SWUSeatsInPlayerOrder($caster) as $p) DoDrawCard($p, 1);
    }
}

// The deciding player discards the chosen card from their right neighbour's hand. The mzID is minted in
// the DECIDER's frame, so resolve it there and read the owner off the mzID rather than assuming.
$customDQHandlers["TS26_80#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $remaining = array_values(array_filter(explode(',', (string)($parts[0] ?? '')), fn($v) => $v !== ''));
    $mz = $lastDecision ?? '';
    if ($mz !== '' && $mz !== '-' && $mz !== 'PASS') {
        $obj = GetZoneObject($mz);
        if (!SWUObjGone($obj)) {
            $owner  = SWUMzOwner($mz, intval($player));
            $cardID = $obj->CardID;
            $obj->Remove();
            SWUAddToDiscard($owner, $cardID, 'HAND');
            DecisionQueueController::CleanupRemovedCards();
            AddGameLogEntry('DISCARD', 'P' . intval($player) . ' discarded ' . GameLogCardRef($cardID) . " from P{$owner}'s hand");
        }
    }
    _SWUTs26_80Ask(intval($player), $remaining);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TS26_80:0"] = function($player, $mzID = '') {
    global $playerID; $savedPID = $playerID;
    $P = intval($player);
    // "Each player reveals their hand" — public, every live seat (was the caster + OtherPlayer only).
    foreach (SWUSeatsInPlayerOrder($P) as $rp) {
        $refs = [];
        foreach (GetHand($rp) as $c) { if (empty($c->removed)) $refs[] = GameLogCardRef($c->CardID); }
        AddGameLogEntry('REVEAL', "P{$rp} revealed their hand: " . (empty($refs) ? '(empty)' : implode(', ', $refs)), 'ALL');
        if (!empty($refs) && function_exists('_SWUSec016React')) _SWUSec016React($rp);
    }
    $playerID = $savedPID;
    _SWUTs26_80Ask($P, SWUSeatsInPlayerOrder($P));
};
