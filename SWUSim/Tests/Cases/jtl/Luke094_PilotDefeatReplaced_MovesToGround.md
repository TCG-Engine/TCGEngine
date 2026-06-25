# JTL_094 Luke (pilot upgrade) — If this UPGRADE would be defeated, you may instead move him to the
# ground arena as a unit and exhaust him. Luke is attached as a pilot on SEC_214. P2 plays JTL_078 to
# defeat the Vehicle SEC_214; as the host leaves play Luke would be defeated, but his controller (P1)
# moves him to the ground arena as an exhausted unit instead — so SEC_214 is discarded but Luke is not.

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
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_094
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1
