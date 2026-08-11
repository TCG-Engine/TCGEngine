<?php
// HMW_136
// Cost 3 - Lifetree Caravan - [Command] - Power 2 - HP 1 - Trait: Ewok
// Text: When Played: If you control 3 or more units (including this one), you may resource the top card of your deck.

$whenPlayedAbilities["HMW_136:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    // "(including this one)" — the Caravan is already in play when its When Played resolves, so a plain
    // count of friendly units in play is inclusive by construction. Not arena-restricted: "units you
    // control" spans both arenas (and GetUnitsInPlay includes tokens and a deployed leader unit).
    $units = 0;
    foreach (GetUnitsInPlay(intval($player)) as $u) { if (!SWUObjGone($u)) $units++; }
    if ($units < 3) return;
    // No deck = the effect can do nothing, so don't raise a prompt the player can only waste
    // (the SEC_186/SEC_210 "skip the pointless offer" rule).
    if (count(GetDeck(intval($player))) === 0) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1,
        tooltip: "Resource_the_top_card_of_your_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "HMW_136#0", 1);
};

$customDQHandlers["HMW_136#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    if (count(GetDeck(intval($player))) === 0) return;   // re-check: the board can move behind a decision
    // "resource the top card" with NO "and ready it" rider → the resource enters EXHAUSTED.
    SWURampResourceExhausted(intval($player), "myDeck-0");
};
