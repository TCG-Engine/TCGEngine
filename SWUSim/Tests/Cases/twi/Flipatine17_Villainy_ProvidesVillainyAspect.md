# TWI_017 "Flipatine" (VILLAINY face, flipped) provides Cunning + Villainy (NOT Heroism). SOR_128
# (Aggression,Villainy, cost 1) under a Vigilance base: Villainy is waived by Palpatine, only Aggression
# is unmatched (+2), so it costs 3 (6→3). Proves the flip actually toggled the provided alignment to
# Villainy. (Also: no phantom leader unit — the played unit is the only ground unit.)
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:6;handCardIds:SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_128
P1RESAVAILABLE:3
