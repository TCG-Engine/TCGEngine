# SOR_167 Force Throw — choose YOURSELF while holding 2+ other cards: the caster picks which to discard
# (SEC_080, cost 2), then (controlling Force unit SOR_051 Luke) may deal 2 to a unit — onto the enemy
# SOR_046 (3/7, survives). Exercises the same-player 2-card discard path (caster's own MZCHOOSE).
## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_167
WithP1Hand: SEC_080
WithP1Hand: SOR_128
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_128
P2GROUNDARENAUNIT:0:DAMAGE:2
P1DISCARDCOUNT:2
