# OnAttack_GiveAnother
#// ASH_157 Danger Squadron Wingmen (Space, 4/5) — On Attack: you may give an Advantage token to another
#// unit. Attacks P2's base; gives an Advantage token to a friendly Marine (another unit).
## GIVEN
CommonSetup: rrw/rrk
WithP1SpaceArena: ASH_157:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# OnAttack_Decline
#// ASH_157 Danger Squadron Wingmen — the Advantage grant is optional. Declining gives no token; the attack
#// still deals 4 to the base.
## GIVEN
CommonSetup: rrw/rrk
WithP1SpaceArena: ASH_157:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0
