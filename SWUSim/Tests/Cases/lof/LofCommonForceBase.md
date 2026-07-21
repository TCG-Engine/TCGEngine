# FriendlyForceUnitAttacks_GainForce
#// LOF common Force base (LOF_030 The Holy City) — "When a friendly Force unit attacks: create your Force
#// token." P1's Force unit SOR_061 (Guardian of the Whills) attacks the enemy base; only P1 gains the Force.

## GIVEN
CommonSetup: yyw/ggw/{myBase:LOF_030}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2NOFORCE

---

# MultipleForceUnitsAttack_StillForce
#// LOF_030 — the trigger fires per attack. Two friendly Force units attack in turn; P1 has the Force after
#// the first and still has it after the second.

## GIVEN
CommonSetup: yyw/ggw/{myBase:LOF_030}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArena: SOR_061:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AttackGroundArena:1:BASE

## EXPECT
P1HASFORCE
P2NOFORCE

---

# EnemyForceUnitAttacks_NoForce
#// LOF_030 — the base is P1's; when the ENEMY's Force unit attacks (P2's SOR_061 into P1's base), it is not
#// friendly to the base's owner, so nobody gains the Force.

## GIVEN
CommonSetup: yyw/ggw/{myBase:LOF_030}
WithP2GroundArena: SOR_061:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2NOFORCE

---

# FriendlyNonForceUnitAttacks_NoForce
#// LOF_030 — a friendly NON-Force unit (SOR_046) attacking does not satisfy the Force-trait condition, so no
#// Force token is created.

## GIVEN
CommonSetup: yyw/ggw/{myBase:LOF_030}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2NOFORCE
