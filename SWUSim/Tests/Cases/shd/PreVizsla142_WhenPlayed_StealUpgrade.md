# SHD_142 Pre Vizsla (Unit, cost 7, Villainy/Aggression, Ground) — "When Played/On Attack: You may pay the
# cost of an upgrade attached to another non-Vehicle unit. If you do, take control of that upgrade and
# attach it to this unit, if able." P1 plays Pre Vizsla; P2's SOR_046 wears SOR_069 (cost 1). P1 pays 1
# and moves SOR_069 onto Pre Vizsla — SOR_046 loses it, Pre Vizsla gains it.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 15
WithP1Hand: SHD_142
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_069
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
