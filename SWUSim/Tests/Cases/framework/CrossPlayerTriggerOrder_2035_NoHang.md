# Repro of the game-2035 cross-player trigger-order hang (from the 2035 snapshot).
# P1's deployed Cad Bane (ASH_011, myGroundArena-1) attacks the enemy Sentinel Captain Typho
# (SEC_098, theirGroundArena-2). That triggers TWO abilities at once: Cad Bane's On Attack (mine)
# and Typho's On Defense (opponent's) → the active player chooses which player resolves first.
# P1 picks "Yours" (YES). Per CR 7.6.10 that must AUTO-resolve P1's single trigger (Cad Bane's
# "deal 1" ping — targeting Typho) with NO intermediate "choose trigger" pick, then AUTO-move to
# the opponent's single trigger (Typho's disclose, declined here), then finish combat.
# BUG: the effect stack hangs after the ping and neither player can act.
# Expected: combat resolves — Cad Bane pings Typho (1) then deals 4 (Typho 5HP → defeated); Typho
# counters 4 onto Cad Bane (7HP → survives). No pending decisions remain.

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:ASH_011:true:true:true:0:1;
  myBase:ASH_020;
  myBaseDamage:19;
  theirLeader:ASH_005:false:false:false:0;
  theirBase:JTL_024;
  theirBaseDamage:3;
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [ASH_147:0:0]
WithP2GroundArena: [LOF_096:0:3:LOF_045 SEC_094:0:0 SEC_098:0:0]
WithP2Hand: [LAW_133 JTL_095]

## WHEN
- P1>AttackGroundArena:1:theirGroundArena-2
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-2
- P2>AnswerDecision:-

## EXPECT
P1NODECISION
P2NODECISION
P2GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_011
P1GROUNDARENAUNIT:1:DAMAGE:4
