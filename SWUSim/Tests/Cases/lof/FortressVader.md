# ForceUnitAttacks_CreatesForceToken
#// LOF_026 Fortress Vader — "When a friendly Force unit attacks: The Force is with you (create your
#// Force token)." Outer Rim Mystic (LOF_112, a Force unit) attacks the enemy base; the base's reactive
#// trigger fires during the OnAttack window and P1 gains the Force (player state, no real token card).

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_026;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_112:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASFORCE
P2BASEDMG:2

---

# NonForceUnitAttacks_NoForce
#// LOF_026 Fortress Vader — the trigger only fires for a friendly *Force* unit. A non-Force attacker
#// (Battlefield Marine SOR_095, no Force trait) attacking past the same base must NOT create the Force.
#// (Absence guard — passes pre-implementation; stays meaningful once the positive case works.)

## GIVEN
CommonSetup: rbk/bbk/{
  myBase:LOF_026;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NOFORCE
P2BASEDMG:3
