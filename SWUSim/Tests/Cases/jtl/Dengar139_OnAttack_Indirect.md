# JTL_139 Dengar (pilot) — Attached gains "On Attack: deal 2 indirect to a player (3 if attached is an
# Underworld unit)." On a non-Underworld host SOR_237 (2+1 power = 3), attacking the base: 3 combat + 2
# indirect = 5 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5
