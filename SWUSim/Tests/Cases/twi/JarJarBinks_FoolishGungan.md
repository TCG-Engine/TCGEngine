# OnAttack_RandomStrikeFires
#// TWI_202 Jar Jar Binks (Unit 2/3, Ground, cost 2, Cunning/Cunning, Gungan) — "On Attack: Deal 2 damage
#// to a random unit or base." The random target (units + both bases are always in the pool) can't be pinned
#// in a scripted test, so this asserts the ability fired and dealt 2 via a log tag; a live smoke test
#// confirms a real target took 2.

## GIVEN
CommonSetup: yyk/bbw/{}
P1OnlyActions: true
WithP1GroundArena: TWI_202:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
#// User-approved 2026-09-11: asserts the real damage line (whichever random target was hit) instead of the
#// old 'TWI202_HIT' tag, which printed in the live game log.
LOGCONTAINS:P1's [[TWI_202|Jar Jar Binks]] dealt 2 damage to
P1GROUNDARENAUNIT:0:EXHAUSTED
