# SHD_182 Bravado — full cost (5) when no enemy unit defeated this phase.
# P1 has 5 resources. If discount were wrongly applied, P1RESAVAILABLE would be 2.

## GIVEN
CommonSetup: grw/grw/{myResources:5;handCardIds:SHD_182}
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P2GROUNDARENAUNIT:0:READY
