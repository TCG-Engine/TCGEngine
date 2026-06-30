# SEC_198 Bail Organa (Ground, 2/?, Cunning/Heroism) — On Attack: you may discard a card from your
#   hand. If you do, create a Spy token. Attack base, discard SOR_095 → create a Spy.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_198:1:0
WithP1Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:2
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
