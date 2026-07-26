<?php
// SHD_135
// Cost 2 - Kylo's TIE Silencer - Ruthlessly Efficient - [Villainy,Aggression] - Power 3 - HP 2
// Text: Action: If this unit was discarded from your hand or deck this phase, play it from your discard pile (paying its cost).

$cardDiscardedHandlers['SHD_135:0'] = function(int $player, object $entry): void {
    if ($entry->From === 'HAND' || $entry->From === 'DECK') {
        $entry->Modifier = 'TPP';
    }
};
