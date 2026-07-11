# SHD_242 Gideon's Light Cruiser — the free-played unit's OWN When Played fires exactly once.
# SEC_240 (Space, 3/5, Villainy, cost 3) has "When Played: Deal 2 damage to this unit." Free-played via
# SHD_242, it must end with DAMAGE:2 (a single fire), not 4 (a double fire from both the auto entry-trigger
# and a manual re-fire) — guards the nested-play trigger drain.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;myLeader:SHD_007:1:1}
P1OnlyActions: true
WithP1Hand: SHD_242
WithP1Hand: SEC_240

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:1:CARDID:SEC_240
P1SPACEARENAUNIT:1:DAMAGE:2
