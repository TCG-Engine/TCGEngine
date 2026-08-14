<?php
// SOR_091
// The Emperor's Legion
// Text: Return each unit in your discard pile that was defeated this phase to your hand.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["SOR_091:0"] = function($player, $mzID = '') {
// The Emperor's Legion — "Return each unit in your discard pile that was
                          // defeated this phase to your hand." The defeated-this-phase multiset is
                          // counted per CardID on the owner (SWU_DEFEATED_CARD_{id}); return up to
                          // that many copies of each from the player's discard (CardID-keyed because
                          // discard UniqueIDs don't survive the serialization boundary; counts do).
            global $playerID;
            $playerID = intval($player);
            $discard  = GetDiscard(intval($player));
            $remaining = [];  // cardID → how many still to return this resolution
            $toReturn  = [];
            for ($i = 0; $i < count($discard); $i++) {
                $o = $discard[$i];
                if (isset($o->removed) && $o->removed) continue;
                // DEFEAT PROVENANCE (candidate #9 fix, 2026-08-14): the CardID multiset alone can't
                // tell a defeated copy from one that arrived by hand-discard/mill AFTER the defeated
                // copy left the pile — the stale count wrongly returned it. A defeated unit's discard
                // entry is the only way a UNIT lands here with From='PLAY' (hand discards stamp
                // 'HAND', mill 'DECK', seeded entries nothing), so require it alongside the count.
                if (($o->From ?? '') !== 'PLAY') continue;
                $cid = $o->CardID ?? '';
                if (!isset($remaining[$cid])) {
                    $remaining[$cid] = GlobalEffectCount(intval($player), 'SWU_DEFEATED_CARD_' . $cid);
                }
                if ($remaining[$cid] > 0) { $toReturn[] = $o; $remaining[$cid]--; }
            }
            foreach ($toReturn as $o) {
                $o->removed = true;
                AddHand(intval($player), CardID:$o->CardID);
            }
            DecisionQueueController::CleanupRemovedCards();
            return;
};
