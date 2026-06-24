# ASH_050 Morgan Elsbeth (Ground, 5/6, Support) — When Defeated: you may give a unit -2/-2 for this
# phase. Pre-damaged to 1 HP, Morgan attacks SOR_046 (3/7, survives) and dies to the 3 counter; her
# WhenDefeated gives -2/-2 to the bystander SEC_080 (3/3 → 1/1).
## GIVEN
CommonSetup: bbk/bbk
WithP1GroundArena: ASH_050:1:5
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:POWER:1
P2GROUNDARENAUNIT:1:HP:1
