# ASH_179 Boba Fett's Rancor (Ground, 8/9, cost 8) — When Played: deal 5 to your base; then deal 5 to an
# enemy ground unit; then deal 5 to the same unit. P1's base takes 5; SOR_046 (3/7) takes 5+5 = 10 and is
# defeated.
## GIVEN
CommonSetup: rrk/rrk/{myResources:8;handCardIds:ASH_179}
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:5
P2GROUNDARENACOUNT:0
