# TS26_052 Sith Traditions — attached unit also gains "When Defeated: give an Experience token to a
# friendly unit." SEC_080 (wearing it, pre-damaged) attacks LAW_124 and dies to the counter; its
# When-Defeated gives 1 Experience to the surviving friendly SOR_046 (3 power → 4).
## GIVEN
CommonSetup: ggk/rrk
WithP1GroundArena: [SEC_080:1:3 SOR_046:1:0]
WithP1GroundArenaUpgrade: 0:TS26_052
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:4
