# JTL_139 Dengar (pilot) — On Attack: deal 2 indirect to a player (non-Underworld host). With an enemy
# unit in play the damaged player (P2) ASSIGNS the 2 indirect, splitting it across a unit AND the base:
# 1 to their 1-HP SOR_128 (defeats it) + 1 to their base. Host SOR_237 (2 power +1 from JTL_139 = 3)
# attacks P2's base for 3 combat, so P2 base = 3 combat + 1 indirect = 4; SOR_128 is defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION
