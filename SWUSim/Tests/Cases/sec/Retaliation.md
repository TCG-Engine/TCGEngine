# DefeatBaseDamager
#// SEC_077 Retaliation (Event, Vigilance, cost 5) — "Defeat a unit that dealt damage to a base this phase."
#//   SOR_095 attacks P2's base (marked), then SEC_077 defeats it (the only base-damager this phase).

## GIVEN
CommonSetup: bbk/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_077

## WHEN
- P1>AttackGroundArena:0
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1GROUNDARENACOUNT:0
P1NODECISION
