# SEC_139 Miraj Scintel (Ground, 3/7) — When Played: you may deal 3 to an UNDAMAGED unit. Hits the
#   undamaged enemy SOR_046.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_139

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
