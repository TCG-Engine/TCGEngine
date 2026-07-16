# CannotAttackBase
#// ASH_034 Wicket, Yub Nub! (3/3, Ewok) — "Saboteur. This unit can't attack bases." With only Wicket
#// on the board, an attack declared at the enemy base is a full no-op: the base takes no damage and
#// Wicket stays ready (it never actually attacked). (Engine: CombatLogic $noBases for ASH_034.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: ASH_034:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:CARDID:ASH_034
P1GROUNDARENAUNIT:0:READY

---

# CanAttackUnit
#// Guard: the can't-attack-BASES restriction does not stop Wicket attacking a UNIT. Wicket (3 power)
#// attacks a 3/7 wall (SOR_046, survives) → the defender takes Wicket's 3 combat damage, proving Wicket
#// attacks units normally.

## GIVEN
CommonSetup: rrk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: ASH_034:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
