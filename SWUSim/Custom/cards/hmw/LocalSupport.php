<?php
// HMW_148 Local Support — Upgrade +1/+3, cost 2, [Command], Supply.
// "When Played: Reveal the top card of your deck. If it shares a trait with a friendly unit, draw it."
// Otherwise the card stays on top. "friendly" spans the team; traits are read object-aware on the unit.

$whenPlayedAbilities["HMW_148:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    $idx = _SWUTopDeckFrontIdx(intval($player));
    if ($idx === -1) return;
    $topID = GetDeck(intval($player))[$idx]->CardID;
    AddGameLogEntry('REVEAL', 'P' . intval($player) . ' revealed ' . GameLogCardRef($topID) . ' (top of deck)', 'ALL');
    $traits = array_filter(array_map('trim', explode(',', (string)(CardTrait($topID) ?? ''))));
    if (empty($traits)) return;
    foreach (SWUFriendlyUnitObjects(intval($player)) as $u) {
        if (!empty($u->removed)) continue;
        foreach ($traits as $t) {
            if (TraitContains($u, $t)) { SWUDrawTopCardFront(intval($player), true); return; }
        }
    }
};
