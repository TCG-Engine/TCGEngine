# TWI_017 "Flipatine" (VILLAINY face, flipped) provides Cunning+Villainy but NOT Heroism. A Heroism card
# (LAW_180 Aggression,Heroism) under a Vigilance base pays the full +4, costing 5 (6→1). Proves the flip
# swapped the provided alignment away from Heroism (it would cost 3 if Heroism were still provided).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:6;handCardIds:LAW_180}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_180
P1RESAVAILABLE:1
