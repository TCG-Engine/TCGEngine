<?php
// SOR_152
// For a Cause I Believe In
// Text: Reveal the top 4 cards of your deck. For each [Heroism] card revealed this way, deal 1 damage to an enemy base. You may discard any of the revealed cards and put the rest back on top of your deck in any order.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_152:0"] = function($player, $mzID = '') {
// For a Cause I Believe In — "Reveal the top 4 cards of your deck. For each
            // [Heroism] card revealed this way, deal 1 damage to an enemy base. You may discard any of
            // the revealed cards and put the rest back on top of your deck in any order."
            global $playerID;
            $playerID = intval($player);
            $deck = GetDeck(intval($player));
            $ids = [];
            foreach ($deck as $c) {
                if (!empty($c->removed)) continue;
                $ids[] = $c->CardID;
                if (count($ids) >= 4) break;
            }
            if (empty($ids)) return;                       // empty deck → nothing to reveal
            // Reveal publicly, then deal 1 to the enemy base per [Heroism] card revealed.
            $heroism = 0;
            foreach ($ids as $cid) {
                if (strpos(CardAspect($cid) ?? '', 'Heroism') !== false) $heroism++;
            }
            AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . implode(', ', array_map('GameLogCardRef', $ids)));
            if ($heroism > 0) SWUDealDamageToBase($heroism, GetOpponent(intval($player)));
            // Then: discard any of the revealed cards and reorder the rest back on top.
            DecisionQueueController::AddDecision($player, "REVEALARRANGE", implode(',', $ids), 1, "Discard_any_revealed_cards_then_reorder_the_rest_on_top");
            DecisionQueueController::AddDecision($player, "CUSTOM", "REVEALARRANGE_FINALIZE|" . count($ids), 1);
            return;
};
