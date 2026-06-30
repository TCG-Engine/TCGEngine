# SEC_154 Inner Rim Coalition (Ground, 6/5) — When Defeated: you may ready a unit that costs 5 or less.
#   SEC_154 (pre-damaged to 1 HP) attacks SOR_046 and dies to the counter; on defeat P1 readies the
#   exhausted SEC_041 (cost 1).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_154:1:4
WithP1GroundArena: SEC_041:0:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_041
P1GROUNDARENAUNIT:0:READY
