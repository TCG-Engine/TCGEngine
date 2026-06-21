# LAW_233 Galen Erso (Cunning, cost 3) — When Played: you may have an opponent take control (declined
# here). Passive: "Enemy units gain Raid 1 and Saboteur." The enemy SOR_046 gains both.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_233

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
