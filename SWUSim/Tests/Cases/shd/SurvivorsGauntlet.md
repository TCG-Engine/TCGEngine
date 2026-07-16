# SurvivorsGauntlet_OnAttack_MoveUpgrade
#// SHD_064 Survivors' Gauntlet — the On Attack half of the same move ability. Pre-deployed SHD_064
#// (idx0) attacks the base; its On Attack moves SOR_069 from SOR_046 (idx1) to SOR_095 (idx2).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_064:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1

---

# SurvivorsGauntlet_WhenPlayed_MoveUpgrade
#// SHD_064 Survivors' Gauntlet — "When Played/On Attack: You may attach an upgrade on a unit to another
#// eligible unit controlled by the same player." When played, P1 moves SOR_069 from SOR_046 (idx0) to
#// SOR_095 (idx1).

## GIVEN
CommonSetup: bbw/bbw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_064
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
