# SEC_206 — the When-Played debuff is a "may". Declining leaves the enemy SOR_046 at its base 3 power.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_206

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:POWER:3
