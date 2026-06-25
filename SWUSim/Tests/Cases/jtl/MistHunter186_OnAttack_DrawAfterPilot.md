# JTL_186 Mist Hunter — On Attack: If you played a Bounty Hunter or Pilot card this phase, you may draw.
# P1 plays the Bounty Hunter unit SHD_147, then Mist Hunter attacks the base and draws a card.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_186:1:0
WithP1Hand: SHD_147
WithP1Resources: 7
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:3
P1HANDCOUNT:1
P1DECKCOUNT:0
