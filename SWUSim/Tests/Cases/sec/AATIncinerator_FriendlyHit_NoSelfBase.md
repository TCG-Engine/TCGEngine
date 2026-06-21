# SEC_169 AAT Incinerator — if a friendly unit IS damaged by the ability, no self-base damage. Hit one
#   friendly SOR_046 → no penalty.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:0
P1NODECISION
