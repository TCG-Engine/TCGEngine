# OnAttack_DebuffUnit
#// ASH_043 Corona Four (Space, 2/3, cost 2) — On Attack: you may give a unit -2/-0 for this phase. Corona
#// Four attacks P2's base and gives SEC_135 (4/3) -2/-0, dropping it to 2 power; the base takes 2.
## GIVEN
CommonSetup: byk/byk
WithP1SpaceArena: ASH_043:1:0
WithP2GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:POWER:2
P2BASEDMG:2

---

# WhenDefeated_DefeatZeroPower
#// ASH_043 Corona Four — When Defeated: you may defeat a non-leader unit with 0 power. Corona Four (1 HP)
#// attacks SOR_237 (2/3) and dies to the counter; its On Attack debuff is declined, then its When Defeated
#// defeats SOR_118 (a 0-power unit).
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
