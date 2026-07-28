<?php
// SOR_002
// Cost 6 - Iden Versio - Inferno Squad Commander - [Vigilance,Villainy] - Power 4 - HP 4
// Text: Action [Exhaust]: If an enemy unit was defeated this phase, heal 1 damage from your base.
// DeployText: Shielded (When you deploy this leader, give her a Shield token.) / When an enemy unit is defeated: Heal 1 damage from your base.
// Epic Action: If you control 6 or more resources, deploy this leader.

// SOR_002 Iden Versio — Leader Action [Exhaust]:
// "If an enemy unit was defeated this phase, heal 1 damage from your base."
// Passive voice: ANY enemy (opponent-controlled) unit defeated this phase qualifies, regardless of
// who caused it — combat, a sacrifice, or a forced self-defeat (Avenger). That is the opponent's
// SWU_FRIENDLY_DEFEATED flag (a unit they controlled left play via defeat), NOT our own
// SWU_ENEMY_DEFEATED ("you defeated an enemy"), which never sets when the opponent defeats their own.
$leaderAbilities["SOR_002"] = function(int $player): void {
    global $playerID;
    $playerID = $player;
    if (GlobalEffectCount(GetOpponent($player), 'SWU_FRIENDLY_DEFEATED') > 0) {
        OnHealBase($player, $player, 1);
    }
    SWUAfterAction($player);
};
