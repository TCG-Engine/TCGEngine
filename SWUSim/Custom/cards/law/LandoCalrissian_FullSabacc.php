<?php
// LAW_018
// Cost 6 - Lando Calrissian - Full Sabacc - [Cunning,Heroism] - Power 4 - HP 7
// Text: Action [1 resource, Exhaust]: Choose an aspect, then discard a card from a deck. If it has the chosen aspect, create a Credit token.
// DeployText: When Deployed: You may defeat a friendly Credit token. If you do, create 3 Credit tokens.
// Epic Action: If you control 6 or more resources, deploy this leader.

$leaderActionResourceCosts["LAW_018"] = 1;

$leaderAbilities["LAW_018"] = function(int $player): void {
    global $playerID; $playerID = $player;
    if (!SWUExhaustResources($player, SWUApplyCostHalving($player, 1))) { SWUAfterAction($player); return; }
    DecisionQueueController::AddDecision($player, "OPTIONCHOOSE", "Vigilance&Command&Aggression&Cunning&Heroism&Villainy", 1, "Choose_an_aspect");
    DecisionQueueController::AddDecision($player, "CUSTOM", "LAW_018#0", 1);
};

$customDQHandlers["LAW_018#0"] = function($player, $parts, $lastDecision) {   // $lastDecision = aspect
    global $playerID; $playerID = intval($player);
    if (SWUDecisionDeclined($lastDecision)) { SWUAfterAction(intval($player)); return; }
    $aspect = $lastDecision;
    $opp = OtherPlayer(intval($player));
    $mine = _SWUTopDeckFrontIdx(intval($player)) !== -1;
    $theirs = _SWUTopDeckFrontIdx($opp) !== -1;
    if (SeatCountForGame() <= 2) {   // 2-player auto-resolve short-cuts (N-player always offers the picker)
        if (!$mine && !$theirs) { SWUAfterAction(intval($player)); return; }
        if ($mine && !$theirs) { LandoCalrissianFullSabaccMill(intval($player), intval($player), $aspect); return; }
        if ($theirs && !$mine) { LandoCalrissianFullSabaccMill(intval($player), $opp, $aspect); return; }
    }
    DecisionQueueController::AddDecision(intval($player), "OPTIONCHOOSE", "@-&" . SWUDeckPickerLabels(intval($player)), 1, "Discard_from_which_deck?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_018#1|" . $aspect, 1);
};

$customDQHandlers["LAW_018#1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $aspect = $parts[0] ?? '';
    $owner = SWUDecodeDeckPick($lastDecision, intval($player)); // Your_deck→self, Opponent's_deck/P{n}_deck→that player
    LandoCalrissianFullSabaccMill(intval($player), $owner, $aspect);
};

$whenPlayedAbilities["LAW_018:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    if (empty(SWUUsableCreditTokenMzIDs(intval($player)))) return;
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Defeat_a_friendly_Credit_token_to_create_3_Credit_tokens?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_018#2", 1);
};

$customDQHandlers["LAW_018#2"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    $credits = SWUUsableCreditTokenMzIDs(intval($player));
    if (empty($credits)) return;
    if (SWUDefeatCreditToken($credits[0])) SWUCreateCreditToken(intval($player), 3);
};

// ── LAW_018 Lando Calrissian ──────────────────────────────────────────────────
// Front Action [1 resource, Exhaust]: choose an aspect, then discard a card from a deck. If it has the
// chosen aspect, create a Credit token. Deployed When Deployed: you MAY defeat a friendly Credit token;
// if you do, create 3 Credit tokens.
function LandoCalrissianFullSabaccMill(int $player, int $deckOwner, string $aspect): void {
    global $playerID; $playerID = $player;
    $cid = SWUMillTopCard($deckOwner);
    if ($cid !== null && strpos((string)(CardAspect($cid) ?? ''), $aspect) !== false) SWUCreateCreditToken($player, 1);
    SWUAfterAction($player);
}
