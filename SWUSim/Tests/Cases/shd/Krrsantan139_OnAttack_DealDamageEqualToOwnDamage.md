# SHD_139 Krrsantan — "On Attack: Choose a ground unit. You may deal 1 damage to it for each damage on this
# unit." Krrsantan has 3 damage; attacking the base, it deals 3 to the enemy SOR_046 (proves the amount =
# own damage).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SHD_139:1:3
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
