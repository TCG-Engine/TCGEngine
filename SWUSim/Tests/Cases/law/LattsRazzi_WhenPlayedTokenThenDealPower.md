# LAW_039 Latts Razzi (2/1) — When Played: give a Shield or Experience token to this unit, then she
# deals damage equal to her power to an enemy ground unit. Choose Experience (2/1 -> 3/2), deal 3 to
# the enemy SOR_046 (3/7).

## GIVEN
CommonSetup: bgw/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_039

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Experience

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_039
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
