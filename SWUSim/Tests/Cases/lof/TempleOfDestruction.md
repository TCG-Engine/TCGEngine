# 2CombatDamage_NoForce
#// LOF_025 Temple of Destruction — negative/boundary: a 2-power unit attacks P2's base, dealing only 2
#// combat damage (< 3), so no Force token is created.

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2BASEDMG:2

---

# 3CombatDamage_CreatesForce
#// LOF_025 Temple of Destruction — "When a friendly unit deals 3 or more combat damage to an enemy
#// base: The Force is with you." A 3-power unit attacks P2's base, dealing exactly 3 combat damage → P1
#// gains the Force.

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2BASEDMG:3

---

# EnemyCombatDamage_NoForce
#// LOF_025 Temple of Destruction — the trigger requires a FRIENDLY unit dealing the combat damage. An ENEMY
#// unit (P2's Wampa, 4 power) attacking P1's own base deals 4 combat damage to it, but that base is P1's own
#// (not an enemy base) and the attacker is not friendly to P1 — so no Force for either player. Intended: "should
#// not give the Force when an enemy unit deals 3 or more combat damage to a friendly base".

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_025;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP2GroundArena: SOR_164:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2NOFORCE
P1BASEDMG:4

---

# OverwhelmCombatDamage_CreatesForce
#// LOF_025 Temple of Destruction — Overwhelm spillover IS combat damage to the enemy base. Friendly AT-ST
#// (SOR_232, 6 power, Overwhelm) attacks Guardian of the Whills (SOR_061, 2/2): 2 defeats it, the 4 excess
#// spills to P2's base as combat damage (>= 3) → P1 gains the Force. Intended: "should give the Force when a
#// friendly unit deals 3 or more Overwhelm damage".

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_025;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_061:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASFORCE
P2GROUNDARENACOUNT:0
P2BASEDMG:4
