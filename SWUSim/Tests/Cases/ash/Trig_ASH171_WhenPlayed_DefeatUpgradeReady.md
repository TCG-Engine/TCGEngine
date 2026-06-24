# ASH_171 Pegasus Tri-Wing (Space, 3/2, cost 3) — When Played: you may defeat a friendly upgrade; if you
# do, ready this unit. Pegasus enters exhausted; defeating SOR_120 off SOR_095 readies the Pegasus (and
# SOR_095 reverts to 3 power).
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_171}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:POWER:3
