# JTL_149 Red Squadron Y-Wing — On Attack: 3 indirect to the defending player. With an enemy unit in
# play, P2 (the defending/damaged player) ASSIGNS the 3 indirect across a unit AND the base: 1 to their
# 1-HP SOR_128 (defeats it) + 2 to their base. The Y-Wing (power 1) attacks the base for 1 combat, so
# P2 base = 1 combat + 2 indirect = 3; SOR_128 is defeated. No You/Opponent choice (it hits the defender).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_149:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P1NODECISION
