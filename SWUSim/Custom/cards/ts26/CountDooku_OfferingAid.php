<?php
// TS26_01
// Cost 7 - Count Dooku - Offering Aid - [Vigilance,Villainy] - Power 6 - HP 7
// Text: Action [Exhaust]: Choose 2 players. They each heal 1 damage from their base and create a Battle Droid token.
// DeployText: Restore 2 (When this unit attacks, heal 2 damage from your base.) / On Attack: Create 2 Battle Droid tokens.
// Epic Action: If you control 7 or more resources, deploy this leader.

// TS26_01 Count Dooku (deployed) — Restore 2 (auto). On Attack: create 2 Battle Droid tokens.
$onAttackAbilities["TS26_01:0"] = function($player, $mzID) {
    global $playerID; $playerID = intval($player);
    SWUCreateUnitTokens(intval($player), 'TS26_T01', 2);
};

// TS26_01 Count Dooku (front) — Action [Exhaust]: choose 2 players; they each heal 1 from their base and
// create a Battle Droid token. (2-player: both players. Deployed side: Restore 2 auto + OnAttack create 2.)
$leaderAbilities["TS26_01"] = function(int $player): void {
    global $playerID; $playerID = intval($player);
    // ⚠ "Choose 2 PLAYERS" — forced at two seats (both), a real pick of 2 out of N above that. The pool is
    // every live seat: the text lets you pick yourself, and in a team game a teammate too, so this uses
    // SWUQueueChoosePlayer rather than OpponentsOf().
    $seats = GetLiveSeatsArray();
    if (count($seats) > 2) {
        SWUQueueChoosePlayer(intval($player), 'TS26_01#P1', "First_player_to_heal_and_create_a_droid?", $seats);
        SWUAfterAction(intval($player));
        return;
    }
    foreach ($seats as $s) { OnHealBase(intval($player), $s, 1); SWUCreateUnitToken($s, 'TS26_T01'); }
    SWUAfterAction(intval($player));
};

// "Choose 2 players. They EACH heal 1 damage from their base and create a Battle Droid token."
$customDQHandlers["TS26_01#P1"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $first = SWUPickedOpponent($lastDecision);
    if ($first <= 0) return;
    $rest = [];
    foreach (GetLiveSeatsArray() as $s) if ($s !== $first) $rest[] = $s;
    SWUQueueChoosePlayer(intval($player), "TS26_01#P2|{$first}", "Second_player_to_heal_and_create_a_droid?", $rest);
};

$customDQHandlers["TS26_01#P2"] = function($player, $parts, $lastDecision) {
    global $playerID; $playerID = intval($player);
    $first  = intval($parts[0] ?? 0);
    $second = SWUPickedOpponent($lastDecision);
    foreach ([$first, $second] as $s) {
        if ($s > 0) { OnHealBase(intval($player), $s, 1); SWUCreateUnitToken($s, 'TS26_T01'); }
    }
};
