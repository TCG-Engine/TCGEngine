# SEC_219 Ebon Hawk — disclose Villainy → defender gets −4/−0 for this attack.
# Ebon Hawk (3/3) attacks SOR_046 (3/7). Disclose SEC_133 (Villainy, no Heroism) → defender's counter
# power 3 − 4 = 0, so Ebon Hawk takes 0 and survives; SOR_046 takes Ebon Hawk's 3 (no +2, Heroism not
# disclosed).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_133

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
