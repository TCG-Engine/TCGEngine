# SOR_167 Force Throw — choose the OPPONENT when they hold 2+ cards: THEY choose which to discard
# (SEC_144 Tempest Assault, cost 4), then the caster (controlling Force unit SOR_051 Luke) may deal
# that cost as damage to a unit — 4 onto the enemy SOR_046 (3/7, survives). Exercises the async
# cross-player discard path: the opponent's choice resolves BEFORE the cost is read / damage offered.
## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: SEC_142
WithP2Hand: SEC_144
WithP1Hand: SOR_167
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myHand-1
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2HANDCOUNT:1
P2HANDCARD:0:SEC_142
P2GROUNDARENAUNIT:0:DAMAGE:4
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
