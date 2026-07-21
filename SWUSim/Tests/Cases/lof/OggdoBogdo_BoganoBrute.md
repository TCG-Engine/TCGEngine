# AttackDefeats_Heals
#// LOF_063 Oggdo Bogdo (5/5) — "When this unit attacks and defeats a unit: heal 2 damage from this unit."
#// The damaged Oggdo (1 damage) attacks and defeats the enemy 3/1, takes 3 counter (→4 damage), then heals
#// 2 (→2 damage).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:1
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# Undamaged_CantAttack
#// LOF_063 Oggdo Bogdo — "This unit can't attack unless it's damaged." An undamaged Oggdo attacking the
#// base is a no-op (the base takes no damage).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0

---

# Damaged_CanAttackBase
#// LOF_063 Oggdo Bogdo — "This unit can't attack unless it's damaged." Once damaged (1 counter), the
#// restriction lifts and it CAN attack the base for its full 5 power. Ref: "should ... attack the
#// opponent's base when damaged".

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5

---

# NoDefeat_NoHeal
#// LOF_063 Oggdo Bogdo — the heal only fires on "attacks AND defeats a unit". A 1-damage Oggdo attacks a
#// 3/7 Consular Security Force (SOR_046): it deals 5 (survives with 5 dmg), takes 3 counter → 4 damage, and
#// does NOT heal because the defender survived. Ref: "should not heal himself if he doesn't kill defender".

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:1
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# DiesSimultaneously_NoHeal
#// LOF_063 Oggdo Bogdo — if it dies to the counter in the same combat, no heal happens. A 1-damage Oggdo
#// (5/5) attacks a 4/5 Wampa (SOR_164): Oggdo deals 5 (defeats the Wampa) but takes 4 counter → 5 total on a
#// 5-HP body, so both are defeated simultaneously. Oggdo goes to discard, no heal. Ref: "should not heal
#// himself if he dies before".

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_063:1:1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
