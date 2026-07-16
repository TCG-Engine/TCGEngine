# PlayTraitSharingUnit
#// TWI_225 Now There Are Two of Them (Event, cost 3, Cunning, Trick) — "If you control exactly one unit,
#// play a non-Vehicle unit from your hand that shares a Trait with the unit you control. It costs 5 less."
#// P1 controls only SOR_095 (Rebel/Trooper); SOR_046 (Rebel/Trooper, non-Vehicle) plays for free (3 - 5,
#// floored, plus off-aspect).

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: [TWI_225 SOR_046]
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1RESAVAILABLE:0
