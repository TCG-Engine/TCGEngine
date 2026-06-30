# SWUSim Replay Schema
End Game Final Blow
## GIVEN
CommonSetup: grw/grw/{theirBaseDamage:27}
P1Deck: [SOR_095]
P2Deck: [ ]
WithP1GroundArena: SOR_095:1:1
WithInitiativePlayer: 1
WithInitiativeClaimed: false
## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1WIN
P2BASEDMG:30
P1BASEDMG:0
P1RESCOUNT:0
P2RESCOUNT:0