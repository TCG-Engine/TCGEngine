# JTL_176 Shoot Down (event) — Deal 3 to a space unit; if it is defeated this way, you may deal 2 to a
# base. The TIE (SOR_225, 2/1) is defeated by the 3, so P1 then deals 2 to P2's base.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_176
WithP1Resources: 2
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:2
