# ASH_041 Outcast — "including this one": when ASH_041 itself enters play it buffs itself +1/+0 for this
# phase, so it enters at power 2 (base 1 + 1).
## GIVEN
CommonSetup: ryk/ryk/{myResources:2;handCardIds:ASH_041}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:ASH_041
P1SPACEARENAUNIT:0:POWER:2
