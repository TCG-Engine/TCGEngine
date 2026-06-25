# JTL_041 Annihilator — the defeat is optional ("You may defeat an enemy unit"). When P1 DECLINES
# (AnswerDecision:-), no enemy unit is defeated, so there is NO name-hunt and — critically — NO peek:
# P2's hand and deck are never shown (no OK popups are queued). P2 keeps its unit, hand copy, and deck
# copy; nothing is discarded and no decision is left pending. Proves the peek is gated on a defeat.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2SpaceArena: SOR_225:1:0
WithP2Deck: SOR_225
WithP2Hand: SOR_225

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2DECKCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION
