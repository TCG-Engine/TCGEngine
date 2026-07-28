<?php
// LAW_204
// Every Day, More Lies
// Text: Each player discards a card from their hand.

// When Played (event) — migrated from OnPlayEvent. Active player first, then opponent (SEC_147
// each-player-discard pattern). $cardID is hardcoded to this card's literal (the played event still
// sits in the caster's hand until block 10 and must be excluded).
$whenPlayedAbilities["LAW_204:0"] = function($player, $mzID = '') {
    global $playerID;
    foreach ([intval($player), OtherPlayer(intval($player))] as $p) {
        $playerID = $p;
        $hand = array_values(ZoneSearch("myHand", null));
        // The just-played event still sits in the CASTER's hand (discarded at block 10); exclude it.
        if ($p === intval($player)) {
            $excluded = false; $filtered = [];
            foreach ($hand as $mz) {
                $o = GetZoneObject($mz);
                if (!$excluded && $o !== null && ($o->CardID ?? '') === 'LAW_204') { $excluded = true; continue; }
                $filtered[] = $mz;
            }
            $hand = $filtered;
        }
        if (!empty($hand)) SWUQueueChooseTarget($p, $hand, "Discard_a_card_from_your_hand", "DISCARD_FROM_OWN_HAND|" . $p);
    }
};
