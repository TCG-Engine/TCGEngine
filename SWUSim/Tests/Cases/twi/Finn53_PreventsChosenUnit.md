# TWI_053 Finn (Unit 3/4, unique) — "When this unit completes an attack: Choose a unique unit. For this
# phase, if damage would be dealt to that unit, prevent 1 of that damage." Finn attacks the base, chooses
# himself (unique), then P1's Open Fire (SOR_172, deal 4) hits him → he takes 4 - 1 = 3 (survives).
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:SOR_172}
P1OnlyActions: true
WithP1GroundArena: TWI_053:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_053
P1GROUNDARENAUNIT:0:DAMAGE:3
