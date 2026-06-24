# ASH_038 Purrgil Ultra — the same ability also triggers When Defeated. Purrgil (pre-damaged to 1 HP)
# attacks SOR_237 (2/3) and dies to the counter; its When Defeated returns SEC_135 to hand (the deal-damage
# rider then fizzles since no unit remains to target).
## GIVEN
CommonSetup: gyk/gyk
WithP1SpaceArena: ASH_038:1:9
WithP2SpaceArena: SOR_237:1:0
WithP1GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
