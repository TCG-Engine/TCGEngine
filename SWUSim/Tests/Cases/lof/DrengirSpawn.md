# ExpEqualToCost
#// LOF_086 Drengir Spawn (3/3) — Overwhelm + "attacks and defeats a unit: give it Experience tokens equal
#// to the defeated unit's cost." It attacks a pre-damaged cost-3 Sentinel (2 power), defeats it (taking 2
#// counter), and gains 3 Experience tokens.

## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_086:1:0
WithP2GroundArena: SOR_063:1:2

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3

---

# Cost1_OneExp
#// LOF_086 — defeating a cost-1 unit (Alliance Dispatcher 1/2) gives 1 Experience token.
## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_086:1:0
WithP2GroundArena: SOR_093:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Cost0_NoExp
#// LOF_086 — defeating a cost-0 unit (Porg) grants NO Experience tokens (cost 0).
## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_086:1:0
WithP2GroundArena: LOF_254:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# AttackBase_NoExp
#// LOF_086 — attacking the base is not defeating a unit, so no Experience is granted.
## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_086:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# CantDefeat_NoExp
#// LOF_086 (3/3) — attacking Wilderness Fighter (2/4): 3 dmg does not defeat its 4 HP, so no defeat and
#// no Experience; Drengir Spawn survives the 2 counter.
## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_086:1:0
WithP2GroundArena: SOR_064:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# TradeWithMarine_BothDefeated
#// LOF_086 (3/3) — attacking Battlefield Marine (3/3): both deal lethal, both are defeated. Drengir Spawn
#// dies in the trade (its own defeat), so it never sits in the arena with Experience.
## GIVEN
CommonSetup: ggk/rrw
P1OnlyActions: true
WithP1GroundArena: LOF_086:1:0
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
