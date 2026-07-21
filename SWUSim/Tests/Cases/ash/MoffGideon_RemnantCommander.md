# ReturnImperialFromDiscard
#// ASH_097 Moff Gideon (Ground, 2/5, Sentinel) — When Defeated: you may return a non-unique Imperial unit
#// from your discard pile to your hand. Pre-damaged to 1 HP, Gideon attacks SOR_046 and dies; his
#// WhenDefeated returns SEC_080 (non-unique Imperial unit, seeded in the discard) to hand.
## GIVEN
CommonSetup: ggk/ggk/{discardCardIds:SEC_080}
WithP1GroundArena: ASH_097:1:4
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myDiscard-0
## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1

---

# WhenDefeated_Decline_NoReturn
#// ASH_097 Moff Gideon — the When Defeated return is optional. A pre-damaged Gideon dies attacking SOR_046;
#// P1 declines, so the Imperial SEC_080 stays in the discard (nothing returns to hand).
## GIVEN
CommonSetup: ggk/ggk/{discardCardIds:SEC_080}
WithP1GroundArena: ASH_097:1:3
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1HANDCOUNT:0
