# LookHand_DiscardEvent
#// JTL_207 — Look at an opponent's hand and discard an event from it. P2 holds two events (SOR_172,
#// SOR_200) and a unit (SOR_095); P1 may only target the events and discards SOR_172.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_207
WithP1Resources: 5
WithP2Hand: SOR_172
WithP2Hand: SOR_200
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0

## EXPECT
P2HANDCOUNT:2
P2DISCARDCOUNT:1
