# SEC_212 Libertine — "gets +1/+0 for each captured card it's guarding." Via SEC_106, SEC_212 captures
#   the enemy SOR_095 → it now guards 1 captive → power 3 + 1 = 4.

## GIVEN
CommonSetup: ggw/rrk/{myResources:6}
P1OnlyActions: true
WithP1SpaceArena: SEC_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_106

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:4
P2GROUNDARENACOUNT:0
P1NODECISION
