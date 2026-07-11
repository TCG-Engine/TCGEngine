# SHD_109 Endless Legions — a free-played unit-resource fires its own When Played (one at a time).
# SEC_240 (Space, 3/5, "When Played: Deal 2 damage to this unit.") sits as a resource. Played via the loop,
# it enters the space arena and ends at DAMAGE:2 (a single fire), proving the nested play resolves its
# entry trigger exactly once.

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_240:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SEC_240
P1SPACEARENAUNIT:0:DAMAGE:2
