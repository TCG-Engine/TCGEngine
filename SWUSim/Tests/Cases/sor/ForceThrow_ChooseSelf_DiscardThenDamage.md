# SOR_167 Force Throw (Event, cost 1, Aggression) — "Choose a player. That player discards a card from
# their hand. Then, if you control a FORCE unit, you may deal damage to a unit equal to the cost of the
# discarded card." P1 chooses ITSELF → P1 discards its only remaining hand card (SEC_080, cost 2) →
# controls a Force unit (SOR_051 Luke) → may deal 2 to the enemy SOR_046 (3/7, survives). Discard pile holds
# both Force Throw and SEC_080.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SOR_167
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:2
P2GROUNDARENAUNIT:0:DAMAGE:2
