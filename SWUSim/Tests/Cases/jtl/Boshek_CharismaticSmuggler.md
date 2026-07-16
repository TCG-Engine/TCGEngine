# AsUpgrade_MillReturnOdd
#// JTL_215 BoShek (pilot) — When played as an upgrade: Discard 2 from your deck; return each odd-cost one
#// to hand. Deck top: SOR_225 (cost 1, odd) and SOR_095 (cost 2, even). Odd → hand, even → discard.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_215
WithP1SpaceArena: SOR_044:1:0
WithP1Deck: SOR_225
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1DISCARDCOUNT:1
