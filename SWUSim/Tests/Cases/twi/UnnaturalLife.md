# ReplayDefeatedUnit
#// TWI_189 Unnatural Life (Event, cost 3, Cunning/Villainy, Force) — "Play a unit that was defeated this
#// phase from your discard pile. It costs 2 resources less and enters play ready." P1's SOR_128 trades and
#// dies this phase; playing Unnatural Life replays it (cost 3 - 2 = 1, ready).

## GIVEN
CommonSetup: yyk/rrk/{myResources:4;handCardIds:TWI_189}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:0
