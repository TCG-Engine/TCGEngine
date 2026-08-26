<?php
// LAW_145
// Cost 2 - R2-D2 - Part of the Plan - [Command,Heroism] - Power 1 - HP 3
// Text: When Played: Search the top 5 cards of your deck for a unit that shares an aspect with a friendly unit, reveal it, and draw it. (Put the other cards on the bottom of your deck in a random order.)

// LAW_145 R2-D2 — When Played: search the top 5 cards for a unit that shares an aspect with a friendly
// unit, reveal it, and draw it.
$whenPlayedAbilities["LAW_145:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;
    $friendlyAspects = [];
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        foreach (explode(',', (string)(CardAspect($u->CardID ?? '') ?? '')) as $a) { $a = trim($a); if ($a !== '') $friendlyAspects[$a] = true; }
    }
    if (empty($friendlyAspects)) return;
    DoTopDeckSearch(intval($player), 5, function($c) use ($friendlyAspects) {
        if (CardType($c) !== 'Unit') return false;
        foreach (explode(',', (string)(CardAspect($c) ?? '')) as $a) { if (isset($friendlyAspects[trim($a)])) return true; }
        return false;
    }, 1);
};
