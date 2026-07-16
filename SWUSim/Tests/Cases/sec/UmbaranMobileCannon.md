# FirstDamagePrevented
#// SEC_067 Umbaran Mobile Cannon (Ground, 7/3) — "The first time this unit would take damage each phase,
#//   prevent that damage." SOR_046 (3 power) attacks SEC_067; the first damage instance is prevented →
#//   SEC_067 takes 0 (and its 7-power counter kills SOR_046).

## GIVEN
CommonSetup: bbk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_067:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
