## GIVEN
# New myhandCardIds / theirhandCardIds aliases seed each hand (legacy handCardIds / theirHandCardIds still work).
CommonSetup: grw/grw/{myResources:2;myhandCardIds:SOR_095;theirhandCardIds:SOR_046,SOR_237}

## WHEN

## EXPECT
P1HANDCOUNT:1
P1HANDCARD:0:SOR_095
P2HANDCOUNT:2
P2HANDCARD:0:SOR_046
P2HANDCARD:1:SOR_237
