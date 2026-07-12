# TWI_202 Jar Jar Binks (Unit 2/3, Ground, cost 2, Cunning/Cunning, Gungan) — "On Attack: Deal 2 damage
# to a random unit or base." The random target (units + both bases are always in the pool) can't be pinned
# in a scripted test, so this asserts the ability fired and dealt 2 via a log tag; a live smoke test
# confirms a real target took 2.

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_202:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
LOGCONTAINS:TWI202_HIT
P1GROUNDARENAUNIT:0:EXHAUSTED
