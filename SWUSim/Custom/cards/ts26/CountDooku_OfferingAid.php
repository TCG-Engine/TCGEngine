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
    $opp = OtherPlayer(intval($player));
    OnHealBase(intval($player), intval($player), 1);
    SWUCreateUnitToken(intval($player), 'TS26_T01');
    OnHealBase(intval($player), $opp, 1);
    SWUCreateUnitToken($opp, 'TS26_T01');
    SWUAfterAction(intval($player));
};
