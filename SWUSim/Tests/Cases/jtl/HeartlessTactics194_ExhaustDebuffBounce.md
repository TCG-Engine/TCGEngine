# JTL_194 Heartless Tactics (event) — Exhaust a unit and give it -2/-0 this phase. Then, if it has 0
# power and isn't a leader, you may return it to its owner's hand. SOR_237 (2/3) drops to 0 power and is
# bounced to P2's hand.

## GIVEN
CommonSetup: byk/bbk/{
  myLeader:JTL_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_194
WithP1Resources: 2
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
