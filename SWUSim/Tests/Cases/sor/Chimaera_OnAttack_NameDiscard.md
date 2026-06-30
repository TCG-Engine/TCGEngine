# SOR_185 Chimaera (Space Unit 8/7, cost 8, Cunning/Villainy, Shielded) — "On Attack: Name a card.
# An opponent reveals their hand and discards a card with that name from it." Chimaera (in play,
# ready) attacks P2's base; the On Attack trigger fires first: P1 names "Mission Briefing"
# (SOR_171). P2 reveals their hand and discards the matching card (SOR_171), keeping the other
# (SEC_080). Then combat deals Chimaera's 8 power to P2's base.

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SOR_171
WithP2Hand: SEC_080

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing
- P1>AnswerDecision:OK

## EXPECT
P2BASEDMG:8
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_171
P2DISCARDUNIT:0:FROM:HAND
