# TwoThenTwo
#// LAW_208 Collateral Damage (Aggression event, cost 3) — "Deal 2 damage to a unit. Then, deal 2 damage
#// to a base or another unit in the same arena." Deal 2 to SOR_046, then 2 to the other ground unit SOR_095.

## GIVEN
CommonSetup: rrk/bgw/{myResources:3}
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: LAW_208

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:1:CARDID:SOR_095
P2GROUNDARENAUNIT:1:DAMAGE:2
