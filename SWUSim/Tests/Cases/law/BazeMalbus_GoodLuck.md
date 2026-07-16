# HealedDealsThatMuch
#// LAW_047 Baze Malbus (6/8, Sentinel) — When 1+ damage is healed from this unit: you may deal that much
#// to a unit. Ezra (LAW_035) heals 2 from the damaged Baze; Baze then deals 2 to the enemy SOR_046.

## GIVEN
CommonSetup: bgw/bgw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: LAW_047:1:2
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_035

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_047
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
