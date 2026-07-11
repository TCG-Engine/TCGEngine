# SHD_242 Gideon's Light Cruiser (Unit, Space, cost 8, Villainy, 7/8, Overwhelm)
#   "When Played: If you control Moff Gideon (as a leader or unit), play a [Villainy] unit that costs
#    3 or less from your hand or discard pile for free."
# P1 controls Moff Gideon (SHD_007) deployed, so its Villainy aspect covers SHD_242 (cost stays 8).
# P1 plays SHD_242 (pays 8 of 10 → 2 left); its When Played offers a free Villainy <=3 unit. P1 picks
# SEC_080 (Command/Villainy, cost 2) from hand — it enters play for FREE (resources stay at 2), proving
# the nested free-play from a unit's When Played drains to the arena.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_080

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_242
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:2
P1HANDCOUNT:0
