# JTL_142 Darth Vader (pilot) — Attached gains "On Attack: may deal 1 to a unit; if a unit is defeated
# this way, may deal 1 to a unit or base." The host attacks SOR_044; the granted On Attack kills the
# 1-HP SOR_225 and chains 1 damage to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_142
WithP2SpaceArena: SOR_044:1:0
WithP2GroundArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:1
