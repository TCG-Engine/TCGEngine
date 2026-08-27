<?php
// LAW_048
// Cost 2 - Chio Fain - Four-Armed Slicer - [Vigilance,Aggression] - Power 2 - HP 4
// Text: On Attack: You may choose 2 players. If you do, they each draw a card.

// LAW_048 Chio Fain — On Attack: you may choose 2 players. If you do, they each draw a card. (2-player:
// both players.)
$onAttackAbilities["LAW_048:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    DecisionQueueController::AddDecision(intval($player), "YESNO", "-", 1, tooltip: "Both_players_draw_a_card?");
    DecisionQueueController::AddDecision(intval($player), "CUSTOM", "LAW_048#0", 1);
};

$customDQHandlers["LAW_048#0"] = function($player, $parts, $lastDecision) {
    if ($lastDecision !== 'YES') return;
    global $playerID; $playerID = intval($player);
    // ⚠ "Choose 2 PLAYERS" — at two seats that is forced (both of them) and the old inline pair was right.
    // Above two it is a genuine pick of 2 out of N, and it may include YOURSELF or a teammate, so the
    // pool is every live seat (SWUQueueChoosePlayer), not OpponentsOf().
    $seats = GetLiveSeatsArray();
    if (count($seats) <= 2) { foreach ($seats as $s) DoDrawCard($s, 1); return; }
    SWUQueueChoosePlayer(intval($player), 'LAW_048#P1', "First_player_to_draw_a_card?", $seats);
};

$customDQHandlers["LAW_048#P1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $first = SWUPickedOpponent($lastDecision);
    if ($first <= 0) return;
    $rest = [];
    foreach (GetLiveSeatsArray() as $s) if ($s !== $first) $rest[] = $s;
    SWUQueueChoosePlayer(intval($player), "LAW_048#P2|{$first}", "Second_player_to_draw_a_card?", $rest);
};

$customDQHandlers["LAW_048#P2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $first  = intval($parts[0] ?? 0);
    $second = SWUPickedOpponent($lastDecision);
    if ($first > 0)  DoDrawCard($first, 1);
    if ($second > 0) DoDrawCard($second, 1);
};
