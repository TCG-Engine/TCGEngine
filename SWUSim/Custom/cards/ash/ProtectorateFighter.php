<?php
// ASH_124
// Cost 3 - Protectorate Fighter - [Command] - Power 2 - HP 1
// Text: When Played: If you control a <uq> unit, create a Mandalorian token.

// ASH_124 Protectorate Fighter — When Played: if you control a unique unit, create a Mandalorian token.
$whenPlayedAbilities["ASH_124:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed) && CardUnique($u->CardID ?? '')) { SWUCreateUnitToken(intval($player), 'ASH_T01'); return; }
    }
};
