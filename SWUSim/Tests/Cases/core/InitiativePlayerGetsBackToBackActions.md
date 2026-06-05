## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SOR_095
WithP1GroundArena: SOR_095
WithP2GroundArena: SOR_095
WithInitiativePlayer: 2
WithInitiativeClaimed: false

## WHEN
# Both Battlefield Marines are 3/3 — mutual lethal damage
- P2>Claim
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P2BASEDMG:3
