<?php
// LAW_200
// Cost 2 - Salvaged Blaster - [Aggression] - Upgrade Power 2 - Upgrade HP 0
// Text: Attach to a non-Vehicle unit. / Action: If this upgrade was discarded from your hand or deck this phase, play it from your discard pile (paying its cost).

// LAW_200 Salvaged Blaster (Upgrade) — "Action: If this upgrade was discarded from your hand or deck
// this phase, play it from your discard pile (paying its cost)." Same TPP stamp as SHD_135 (the upgrade
// is then played from discard via the SWUPlayFromDiscard → ActivateCard upgrade route). TPP clears at RGS.
$cardDiscardedHandlers['LAW_200:0'] = function(int $player, object $entry): void {
    if ($entry->From === 'HAND' || $entry->From === 'DECK') {
        $entry->Modifier = 'TPP';
    }
};
