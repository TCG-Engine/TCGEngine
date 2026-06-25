# JTL_240 Fett's Firespray — On Attack: 1 indirect to a player (no Boba Fett controlled → 1). 1 damage
# can't split across two targets, but this verifies the assigner may put it on a UNIT instead of the
# base. With an enemy unit in play P2 assigns the 1 indirect to their 1-HP SOR_128 (defeats it). The
# Firespray (power 4) attacks P2's base for 4 combat; the indirect goes to the unit, so P2 base = 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_240:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION
