# CanStillAttackUnit
#// SOR_072 Entrenched — only BASE attacks are blocked; the host can still attack units. SOR_095 +
#// Entrenched (→ 6/6) attacks an enemy unit (SOR_046, 3/7) for DAMAGE:6.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_072
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6

---

# CantAttackBase
#// SOR_072 Entrenched (Vigilance upgrade, cost 2, +3/+3, Condition) — "Attached unit can't attack
#// bases." SOR_095 + Entrenched (→ 6/6) tries to attack the enemy base: the attack is blocked, so the
#// base takes no damage.

## GIVEN
CommonSetup: rrw/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SOR_072

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
