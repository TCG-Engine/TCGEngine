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
            // "For each [Heroism] card revealed this way, deal 1 damage to AN enemy base" — one lump to a
            // base the caster picks (auto-resolves invisibly at one opponent, so Premier is unchanged).
            //
            // ⚠ ORDER IS LOAD-BEARING, and this is why the arrange step is queued from INSIDE the
            // continuation rather than here. The damage now resolves in a CUSTOM, so queueing the
            // REVEALARRANGE alongside it would put the prompt in the queue BEFORE the damage lands —
            // and the post-win halt could no longer suppress it. LethalToOpponentBase_NoArrangePromptAfterTheWin
            // pins exactly that: all four reveals are Heroism, the base dies, and the prompt must never appear.
            if ($heroism > 0) {
                SWUQueueChooseOpponent(intval($player), 'SOR_152#BASE|' . $heroism . '|' . implode(',', $ids),
                    "Deal_{$heroism}_to_which_opponent's_base?");
                return;
            }
            // No Heroism revealed → no damage step; go straight to discard/reorder.
            _SWUSor152QueueArrange(intval($player), $ids);
            return;
};

function _SWUSor152QueueArrange(int $player, array $ids): void {
    if (empty($ids)) return;
    DecisionQueueController::AddDecision($player, "REVEALARRANGE", implode(',', $ids), 1, "Discard_any_revealed_cards_then_reorder_the_rest_on_top");
    DecisionQueueController::AddDecision($player, "CUSTOM", "REVEALARRANGE_FINALIZE|" . count($ids), 1);
}

$customDQHandlers["SOR_152#BASE"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $n   = max(0, intval($parts[0] ?? 0));
    $ids = ($parts[1] ?? '') !== '' ? explode(',', $parts[1]) : [];
    $opp = SWUPickedOpponent($lastDecision);
    if ($opp > 0 && $n > 0) SWUDealDamageToBase($n, $opp);
    // Queue the arrange step only AFTER the damage has landed, so a lethal hit halts before it.
    _SWUSor152QueueArrange(intval($player), $ids);
};
