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

---

# NGOR_ReturnResolvesForNewController
#// ASH_097 Moff Gideon — the When Defeated "return a non-unique Imperial unit from your discard to your
#// hand" resolves for whoever controls Gideon at defeat. P2 uses No Glory, Only Results (JTL_043) to take
#// control of P1's Gideon and defeat it, so the return pulls from P2's own discard: the non-unique Imperial
#// SEC_080 goes to P2's hand.
## GIVEN
CommonSetup: ggk/bbk/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 10
WithP2Hand: JTL_043
WithP2Discard: SEC_080
WithP1GroundArena: ASH_097:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myDiscard-0
## EXPECT
P1GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
