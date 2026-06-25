## GIVEN
CommonSetup: grw/ggk/{
  myBase:SOR_022;
  theirBase:SOR_022
}
WithInitiativePlayer: 1

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
PHASEISNOT:MAIN
