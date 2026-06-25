# JTL_165 Hunting Aggressor — Indirect damage you deal to opponents is increased by 1. With it in play,
# JTL_240's 1 indirect becomes 2 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_165:1:0
WithP1Hand: JTL_240
WithP1Resources: 12

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:2
