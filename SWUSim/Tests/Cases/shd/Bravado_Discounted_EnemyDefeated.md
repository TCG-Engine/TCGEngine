# SHD_182 Bravado — discounted cost (3 instead of 5) when P1 defeated an enemy unit this phase.
# P1 has exactly 3 resources — could NOT play Bravado at full cost 5, proving discount applied.

## GIVEN
CommonSetup: grw/grw/{myResources:3;handCardIds:SHD_182}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_095:2:0
WithP2GroundArena: SOR_189:2:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENACOUNT:0
