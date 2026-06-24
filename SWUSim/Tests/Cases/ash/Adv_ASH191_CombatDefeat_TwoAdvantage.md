# ASH_191 Shin Hati's Fiend Fighter (Space, 3/1) — When Defeated: you may give 2 Advantage tokens to a
# unit; if NOT defeated by combat, 3 instead. Here ASH_191 attacks SOR_225 (2/1) and dies to the counter
# (combat defeat) → may give 2 Advantage tokens. The bystander SOR_095 receives them.
## GIVEN
CommonSetup: yyk/yyk
WithP1SpaceArena: ASH_191:1:0
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:2
