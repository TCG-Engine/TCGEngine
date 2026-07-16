# PreventsChosenUnit
#// TWI_053 Finn (Unit 3/4, unique) — "When this unit completes an attack: Choose a unique unit. For this
#// phase, if damage would be dealt to that unit, prevent 1 of that damage." Finn attacks the base, chooses
#// himself (unique), then P1's Open Fire (SOR_172, deal 4) hits him → he takes 4 - 1 = 3 (survives).
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

---

# UnchosenUnit_FullDamage
#// TWI_053 Finn — the prevention applies ONLY to the chosen unique unit. Finn chooses himself, but Open
#// Fire (deal 4) then hits a DIFFERENT unit (SOR_046) → that unit takes the full 4 damage (no prevention).
## GIVEN
CommonSetup: rrk/bbw/{myResources:3;handCardIds:SOR_172}
P1OnlyActions: true
WithP1GroundArena: TWI_053:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:4
