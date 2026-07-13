# Twin Suns Phase 3: TWI_185 Ziro the Hutt "When Played: FOR EACH OPPONENT, you may exhaust a unit THAT
# player controls." In a 3-player game this is a separate optional prompt per opponent, each scoped to that
# opponent's own units. P1 plays Ziro, then exhausts P2's unit AND P3's unit (2-player fires one prompt).

## GIVEN
CommonSetup: yyk/bbw/{myResources:5;handCardIds:TWI_185}
WithSeatOrder: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p2GroundArena-0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P2GROUNDARENAUNIT:0:EXHAUSTED
P3GROUNDARENAUNIT:0:EXHAUSTED
