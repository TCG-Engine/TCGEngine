# SEC_069 Nimble Prowess (upgrade, +1/+1) — Attach to a friendly unit. When Played: you may exhaust a
#   unit in attached unit's arena. P1 attaches it to SEC_041 (ground) and exhausts the enemy SOR_046.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: SEC_041:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
