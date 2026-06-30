# SOR_185 Chimaera — name a card NOT in the opponent's hand. P1 names "Mission Briefing", but P2's
# hand is SOR_095 + SOR_128 (neither matches). The opponent still reveals their hand (public log),
# but nothing is discarded.

## GIVEN
CommonSetup: yyk/yyk/{myResources:0}
P1OnlyActions: true
WithP1SpaceArena: SOR_185:1:0
WithP2Hand: SEC_080
WithP2Hand: SOR_128

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Mission Briefing
- P1>AnswerDecision:OK

## EXPECT
P2BASEDMG:8
P2HANDCOUNT:2
P2DISCARDCOUNT:0
LOGCONTAINS:revealed
