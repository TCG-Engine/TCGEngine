<?php
// TWI_127
// Resupply
// Text: Put this event into play as a resource.

// When Played (event) — migrated from OnPlayEvent.
$whenPlayedAbilities["TWI_127:0"] = function($player, $mzID = '') {
// Resupply — "Put this event into play as a resource."
            global $playerID; $playerID = intval($player);
            $mz = _SWUFindDiscardMzID(intval($player), 'TWI_127'); // the event is in discard now
            if ($mz !== null) SWURampResourceExhausted(intval($player), $mz);
            return;
};
