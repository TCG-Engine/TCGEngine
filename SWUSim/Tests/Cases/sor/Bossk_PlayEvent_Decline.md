# SOR_182 Bossk — decline the optional "deal 2 to a unit" reaction.
# Playing an event triggers Bossk, but the player passes (MZMAYCHOOSE decline) → no damage.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2;handCardIds:SOR_251}
WithP1GroundArena: SOR_182:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION
