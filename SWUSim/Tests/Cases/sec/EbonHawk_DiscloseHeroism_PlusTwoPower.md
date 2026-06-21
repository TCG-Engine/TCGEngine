# SEC_219 Ebon Hawk (Ground, 3/3, Cunning) — On Attack: you may disclose Heroism and/or Villainy.
#   Disclosed Heroism → +2/+0 this attack; disclosed Villainy → defender −4/−0 this attack.
# Disclose SEC_148 (has a Heroism icon, no Villainy) → +2/+0 → base takes 3+2 = 5.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_219:1:0
WithP1Hand: SEC_148

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:5
P1NODECISION
