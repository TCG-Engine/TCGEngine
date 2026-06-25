# LOF_026 Fortress Vader — "When a friendly Force unit attacks: The Force is with you (create your
# Force token)." Outer Rim Mystic (LOF_112, a Force unit) attacks the enemy base; the base's reactive
# trigger fires during the OnAttack window and P1 gains the Force (player state, no real token card).

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
