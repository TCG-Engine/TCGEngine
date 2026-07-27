<?php
// SOR_107
// Cost 4 - Command - [Command,Command]
// Text: Choose two, in any order: / Give 2 Experience tokens to a unit. / A friendly unit deals damage equal to its power to a non-unique enemy unit. / Put this event into play as a resource. / Return a unit from your discard pile to your hand.

// SOR_107 PowerStrike continuation: the chosen friendly unit deals its current power to a chosen
// non-unique enemy unit.
$customDQHandlers["SOR_107#0"] = function($player, $parts, $lastDecision) {
    global $playerID;
    $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    $dealer = GetZoneObject($lastDecision);
    if (SWUObjGone($dealer)) return;
    $power = intval(ObjectCurrentPower($dealer));
    if ($power <= 0) return;
    SWUOfferUnitTarget($player, '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => $power, 'side' => 'their',
        'extraFilter' => fn($o) => !CardUnique($o->CardID),
        'prompt' => "Deal_power_to_a_non-unique_enemy_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_107:0"] = function($player, $mzID = '') {
// Command — 2 Exp / friendly deals its power to a non-unique enemy / this→resource / return a unit from discard
            SWUQueueModalChoose(intval($player), 'SOR_107', ['Experience', 'PowerStrike', 'Resource', 'Return'], 2);
            return;
};
