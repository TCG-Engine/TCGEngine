# JTL_049 L3-37 — the replacement is a "may". P2 Takedowns L3-37; her controller (P1) DECLINES the
# replacement, so she is defeated normally and goes to P1's discard. SEC_214 gains nothing.

## GIVEN
P1LeaderBase: SOR_002/SOR_021
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP2Resources: 6
WithP2Hand: SOR_077
WithP1GroundArena: JTL_049:1:0
WithP1GroundArena: SEC_214:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:NO

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_214
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DISCARDCOUNT:1
