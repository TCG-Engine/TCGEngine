# Twin Suns Phase 3 / Group B: TWI_078 "Choose an opponent. Defeat each unit that player controls." In a
# 3-player game the caster PICKS which opponent (via the SWUQueueChooseOpponent OPTIONCHOOSE). Choosing P3
# defeats only P3's unit; P2's unit is untouched. (A bare "theirGroundArena" union would wrongly defeat
# ALL opponents' units — this proves the choose-one-opponent semantics.)

## GIVEN
CommonSetup: bbk/grw/{myResources:15;handCardIds:TWI_078}
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:3
P2GROUNDARENACOUNT:1
P3GROUNDARENACOUNT:0
