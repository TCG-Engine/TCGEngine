<?php
// LAW_206
// Cost 1 - That's a Rock - [Aggression]
// Text: Deal 1 damage to a unit. / When this event is discarded from your hand or deck: You may deal 1 damage to a unit.

// LAW_206 That's a Rock — "When this event is discarded from your hand or deck: You may deal 1 damage
// to a unit." Fires only for HAND/DECK discards (NOT when the event resolves from play).
$cardDiscardedHandlers['LAW_206:0'] = function(int $player, object $entry, ?object $sourceObject = null): void {
    $from = $entry->From ?? '';
    if ($from !== 'HAND' && $from !== 'DECK') return;          // not "discarded from hand or deck"
    if (($GLOBALS['gPlayingEventCardID'] ?? '') === ($entry->CardID ?? '')) return; // the event's own play, not a discard
    global $playerID; $playerID = intval($player);
    SWUOfferUnitTarget(intval($player), '', [
        'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'may' => true,
        'question' => "Deal_1_damage_to_a_unit?", 'prompt' => "Deal_1_damage_to_a_unit",
    ]);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_206:0"] = function($player, $mzID = '') {
// That's a Rock — "Deal 1 damage to a unit." (The "when discarded from hand or
                          // deck" rider lives in $cardDiscardedHandlers['LAW_206:0'].)
            global $playerID; $playerID = intval($player);
            SWUOfferUnitTarget(intval($player), $mzID, [
                'continuation' => 'DEAL_UNIT_DAMAGE', 'amount' => 1, 'prompt' => "Deal_1_damage_to_a_unit",
            ]);
            return;
};
