# SEC_248 B2EMO (Ground, 0/4, Heroism) — Restore 1 (auto) + On Attack: you may disclose HeroismHeroism
#   → give a unit Sentinel for this phase. B2EMO (0 power) attacks the base; On Attack: disclose SEC_148
#   + SEC_153 (Heroism each) → give the friendly SOR_095 Sentinel.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_248:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_148
WithP1Hand: SEC_153

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:myHand-0&myHand-1
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1NODECISION
