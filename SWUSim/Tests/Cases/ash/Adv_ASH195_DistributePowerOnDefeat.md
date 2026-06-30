# ASH_195 Helgait (Ground, 6/4, cost 5) — When Defeated: you may distribute a number of Advantage tokens
# equal to this unit's power (6) among friendly units. Helgait attacks SOR_038 (7/4) and dies to the 7
# counter; its 6 Advantage are piled onto SOR_095 (now 3 + 6 = 9 power, 6 Advantage tokens).
## GIVEN
CommonSetup: yyk/yyk
WithP1GroundArena: ASH_195:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_038:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0:6
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:6
P1GROUNDARENAUNIT:0:POWER:9
