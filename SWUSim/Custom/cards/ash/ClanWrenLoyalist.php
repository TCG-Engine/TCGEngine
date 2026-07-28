<?php
// ASH_107
// Cost 3 - Clan Wren Loyalist - [Command,Heroism] - Power 3 - HP 2
// Text: When Played: Search the top 5 cards of your deck for a card that shares a Trait with a unit you control, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// ASH_107 Clan Wren Loyalist — When Played: search the top 5 cards of your deck for a card that shares a
// Trait with a unit you control, reveal it, and draw it.
$whenPlayedAbilities["ASH_107:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    $myTraits = [];
    foreach (GetUnitsInPlay(intval($player)) as $u) {
        if (empty($u->removed)) {
            foreach (explode(',', (string)(CardTrait($u->CardID ?? '') ?? '')) as $t) { $t = trim($t); if ($t !== '') $myTraits[$t] = true; }
        }
    }
    if (empty($myTraits)) return;
    $traitKeys = array_keys($myTraits);
    DoTopDeckSearch(intval($player), 5, function($cid) use ($traitKeys) {
        foreach ($traitKeys as $t) { if (HasTrait($cid, $t)) return true; }
        return false;
    }, 1);
};
