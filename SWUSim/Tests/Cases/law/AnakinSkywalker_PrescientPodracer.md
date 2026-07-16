# AttackEndReturnHeal
#// LAW_088 Anakin Skywalker (2/4) — When a friendly unit's attack ends: if no other units have attacked
#// this phase, you may return it to its owner's hand. If you do, heal 2 from your base. Anakin (the only
#// attacker) attacks the base, then returns himself and heals 2.

## GIVEN
CommonSetup: byk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: LAW_088:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1BASEDMG:0
