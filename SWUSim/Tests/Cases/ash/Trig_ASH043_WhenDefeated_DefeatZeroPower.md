# ASH_043 Corona Four — When Defeated: you may defeat a non-leader unit with 0 power. Corona Four (1 HP)
# attacks SOR_237 (2/3) and dies to the counter; its On Attack debuff is declined, then its When Defeated
# defeats SOR_118 (a 0-power unit).
## GIVEN
CommonSetup: byk/byk
WithP1SpaceArena: ASH_043:1:2
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_118:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:-
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:0
