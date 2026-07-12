# SHD_109 Endless Legions (Event, cost 14, Command/Command)
#   "Reveal any number of resources you control. Play each unit revealed this way for free (one at a time)."
# P1 has 2 unit-resources (SEC_080, SOR_128, both exhausted) + 14 ready event-resources (SOR_251) to pay
# the 14 cost (Command covered by the g base). Playing SHD_109 exhausts the 14; the reveal loop then offers
# only the 2 UNIT resources. P1 plays both for free (one at a time) — both enter the ground arena, the
# resource count drops by 2 (14 event-resources remain untouched), and the loop auto-ends (no units left).

## GIVEN
CommonSetup: ggk/rrk
P1OnlyActions: true
WithP1Hand: SHD_109
WithP1Resources: 1:SEC_080:0,1:SOR_128:0,14:SOR_251:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myResources-0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESCOUNT:14
P1DISCARDCOUNT:1
