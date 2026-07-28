<?php
// LAW_203
// Cost 1 - Daring Delve - [Aggression]
// Text: Discard 2 cards from your deck. You may return a Aggression card discarded this way to your hand.

// LAW_203 Daring Delve — return the chosen Aggression card (discarded this way) to hand.
$customDQHandlers["LAW_203#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) return;
    SWUReturnFromDiscardToHand(intval($player), $lastDecision);
};

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["LAW_203:0"] = function($player, $mzID = '') {
// Daring Delve — "Discard 2 cards from your deck. You may return an Aggression
                          // card discarded this way to your hand."
            global $playerID; $playerID = intval($player);
            $milled = [];
            for ($i = 0; $i < 2; $i++) { $cid = SWUMillTopCard(intval($player)); if ($cid !== null) $milled[] = $cid; }
            // Find discard slots for the Aggression cards milled this way (distinct slots per copy).
            $targets = []; $usedIdx = [];
            $discard = GetDiscard(intval($player));
            foreach ($milled as $cid) {
                if (strpos((string)(CardAspect($cid) ?? ''), 'Aggression') === false) continue;
                for ($j = 0; $j < count($discard); $j++) {
                    if (in_array($j, $usedIdx, true) || !empty($discard[$j]->removed)) continue;
                    if (($discard[$j]->CardID ?? '') === $cid) { $targets[] = "myDiscard-{$j}"; $usedIdx[] = $j; break; }
                }
            }
            if (empty($targets)) return;
            SWUQueueMayChooseTarget(intval($player), $targets, "Return_an_Aggression_card_to_hand?",
                "Choose_an_Aggression_card_to_return", "LAW_203#0");
            return;
};
