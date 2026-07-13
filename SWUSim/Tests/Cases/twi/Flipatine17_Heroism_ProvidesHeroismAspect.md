# TWI_017 "Flipatine" (HEROISM face) provides Cunning + Heroism. LAW_180 (Aggression,Heroism, cost 1)
# played under a Vigilance base: Heroism is waived by Palpatine, only the Aggression pip is unmatched
# (+2), so it costs 3 (6→3 resources). If Heroism were NOT provided it would cost 5 (→1).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myResources:6;handCardIds:LAW_180}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_180
P1RESAVAILABLE:3
