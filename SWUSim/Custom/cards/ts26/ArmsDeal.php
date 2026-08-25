<?php
// TS26_68
// Cost 2 - Arms Deal - [Aggression]
// Text: You and an opponent each draw 2 cards.

$whenPlayedAbilities["TS26_68:0"] = function($player, $mzID = '') {
    global $playerID; $playerID = intval($player);
    // "You and AN OPPONENT each draw 2" — the caster chooses which opponent shares the draw.
    // ⚠ NO $eligible filter: DoDrawCard ALWAYS does something to the chosen seat — either 2 cards enter
    // their hand, or their base takes damage for each card they cannot draw. There is no live opponent
    // who is unaffected, so an empty-deck opponent is a LEGAL and often PREFERRED pick (it is base
    // damage), exactly like TWI_222's hellbent seat.
    // ⚠ The pick must be made BEFORE any draw: the caster's own draw can change what they know, and the
    // choice is part of the same effect.
    SWUQueueChooseOpponent(intval($player), 'TS26_68#0|' . intval($player),
        "Choose_an_opponent_to_draw_2_with_you");
};

$customDQHandlers["TS26_68#0"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $caster = intval($parts[0] ?? $player);
    $opp    = SWUPickedOpponent($lastDecision);
    if ($opp <= 0 || $opp === $caster) return;
    DoDrawCard($caster, 2);
    DoDrawCard($opp, 2);
};
