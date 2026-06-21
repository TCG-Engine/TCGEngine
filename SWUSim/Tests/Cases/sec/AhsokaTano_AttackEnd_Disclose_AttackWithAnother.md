# SEC_096 Ahsoka Tano (Ground, 2/5, Command/Heroism) — When this unit completes an attack (and
#   survives): you may disclose CommandHeroism → attack with another unit.
# Ahsoka (idx0) attacks P2 base (2 power, survives — bases don't counter). On attack-end: disclose
# SEC_094 (Command,Heroism) → attack with another unit; the only other ready unit is SOR_095 (idx1,
# 3 power) → it attacks the base too. Total base damage 2 + 3 = 5.

## GIVEN
CommonSetup: ggw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_096:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_094

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:EXHAUSTED
P1NODECISION
