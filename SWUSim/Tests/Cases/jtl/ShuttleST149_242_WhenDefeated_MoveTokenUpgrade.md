# JTL_242 Shuttle ST-149 — Shielded + "When Played/When Defeated: You may take control of a token
# upgrade on a unit and attach it to a different eligible unit." JTL_242 attacks JTL_069 (4/7) into a
# lethal counter and dies; its When Defeated takes the Shield token off SOR_095 and moves it to SOR_046.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1SpaceArena: JTL_242:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
