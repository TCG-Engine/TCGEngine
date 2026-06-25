# JTL_094 Luke (pilot upgrade) — "If this upgrade would be defeated, you may instead move him to the
# ground arena as a unit and exhaust him." Luke is attached as a pilot on SEC_214 (owned/controlled by
# P1). P2 plays JTL_078 to defeat the Vehicle; as the host leaves play Luke would be defeated and P1 is
# offered the move-to-ground. P1 DECLINES (No) — so Luke is simply defeated and, like every defeated
# card, goes to his OWNER (P1)'s discard alongside SEC_214. (Discard 2: SEC_214 then JTL_094.)

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
P1DISCARDUNIT:0:CARDID:SEC_214
P1DISCARDUNIT:1:CARDID:JTL_094
