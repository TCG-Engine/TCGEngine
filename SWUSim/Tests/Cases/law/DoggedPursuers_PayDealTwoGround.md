# LAW_198 Dogged Pursuers (Aggression, cost 5) — When Played: you may pay 1 resource. If you do, deal 2
# damage to a ground unit. Pay 1, deal 2 to the enemy SOR_046.

## GIVEN
CommonSetup: rrw/bgw/{myResources:6}
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_198

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
