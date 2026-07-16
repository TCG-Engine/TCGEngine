# AttackDefenderMinus4
#// SEC_229 Catch Unawares (event, cost 2) — Attack with a unit. The defender gets -4/-0 for this attack.
#//   SEC_041 (1/4) attacks SOR_046 (3/7); the defender drops to 0 power → SEC_041 takes 0; SEC_041 deals
#//   its 1 to SOR_046.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_229

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:1
