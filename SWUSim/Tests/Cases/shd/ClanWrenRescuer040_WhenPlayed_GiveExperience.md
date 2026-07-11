# SHD_040 Clan Wren Rescuer (2-cost 1/2 ground) — "When Played: Give an Experience token to a unit." P1
# directs it onto the friendly SOR_046 (3/7 → 4/8).

## GIVEN
CommonSetup: bbw/bbw/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_040
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:8
