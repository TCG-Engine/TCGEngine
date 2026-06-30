# JTL_094 Luke — the move is a "may". P2 defeats SEC_214 (JTL_078); Luke's controller (P1) DECLINES, so
# Luke is defeated along with his host and goes to P1's discard (both SEC_214 and Luke discarded).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 8
WithP2Hand: JTL_078
WithP1GroundArena: SEC_214:1:0
WithP1GroundArenaUpgrade: 0:JTL_094

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
