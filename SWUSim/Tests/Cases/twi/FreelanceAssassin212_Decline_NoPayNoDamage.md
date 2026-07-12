# TWI_212 Freelance Assassin — declining the optional payment (NO) pays nothing and deals no damage; 5
# resources - 3 cost = 2 left.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5;handCardIds:TWI_212}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:2
