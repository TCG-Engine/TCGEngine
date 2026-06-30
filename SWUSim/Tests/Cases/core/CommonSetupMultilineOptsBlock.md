## GIVEN
# A multi-line opts block inside { } parses identically to the inline form (brace-folding parser).
CommonSetup: grw/grw/{
  myResources:5;
  theirResources:3;
  myhandCardIds:SOR_095,SOR_046;
  theirhandCardIds:SOR_237
}

## WHEN

## EXPECT
P1RESCOUNT:5
P2RESCOUNT:3
P1HANDCOUNT:2
P1HANDCARD:0:SOR_095
P1HANDCARD:1:SOR_046
P2HANDCOUNT:1
P2HANDCARD:0:SOR_237
