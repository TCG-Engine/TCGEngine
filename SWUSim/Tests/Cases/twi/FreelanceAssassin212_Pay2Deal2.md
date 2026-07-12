# TWI_212 Freelance Assassin (Unit 4/2, Ground, cost 3, Cunning, Underworld) — "When Played: You may pay
# 2 resources. If you do, deal 2 damage to a unit." Paying (YES) then targeting SOR_046 deals it 2; 5
# resources - 3 cost - 2 pay = 0 left.

## GIVEN
CommonSetup: yyk/bbw/{myResources:5;handCardIds:TWI_212}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:0
