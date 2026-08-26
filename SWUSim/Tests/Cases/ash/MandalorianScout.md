# WhenDefeatedExhaustResource
#// ASH_216 Mandalorian Scout (Ground, 3/3, cost 2) — When Defeated: exhaust a ready friendly resource. The
#// Scout attacks SOR_046 (3/7) and dies to the counter; its WhenDefeated exhausts one of P1's 3 ready
#// resources (3 → 2 ready).
## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
WithP1GroundArena: ASH_216:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:2

---

# NGOR_NewControllerExhaustsResource
#// ASH_216 Mandalorian Scout — the When Defeated "exhaust a ready friendly resource" resolves for whoever
#// controls the Scout at defeat. P2 uses No Glory, Only Results (JTL_043) to take control of P1's Scout and
#// defeat it, so the exhaust hits P2's OWN resource (10 → 5 after paying the cost, then 4) while P1's 5
#// ready resources are untouched.
## GIVEN
CommonSetup: yyk/bbk/{myResources:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 10
WithP2Hand: JTL_043
WithP1GroundArena: ASH_216:1:0
## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:5
P2RESAVAILABLE:4

---

# TeamSuns_SplitIsOfferedWhenBothSidesHaveReadyResources
#// ⚠ USER RULING 2026-08-26: "a FRIENDLY resource" spans the TEAM. For EXHAUST the choice is a COUNT,
#// not a card picker — exhausting leaves the card exactly where it is and only flips its Status, so
#// which of your own resources is chosen is meaningless. That is the same distinction the rest of this
#// family follows: DEFEAT and RETURN move the card (identity matters, so they get a picker), READY and
#// EXHAUST do not (so they get a split).
#//
#// ⚠ This must NOT go through SWUExhaustResources — that is also the COST-PAYMENT function with 15
#// callers, and you can never pay a cost with a teammate's resources. The Scout's own comment already
#// flags it as an EFFECT, not a cost.
#//
#// Both seats hold a ready resource, so either could take the hit and the controller is asked.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: ASH_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP3Resources: 1

## WHEN
- P1>AttackGroundArena:0:P2G0

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P1HASDECISION

---

# TeamSuns_ExhaustTheTEAMMATESResourceInstead
#// Answering 0 ("none from my own") puts the drawback on the teammate: seat 3's resource exhausts and
#// seat 1 keeps its own ready. Asserting BOTH seats is what makes it discriminate.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: ASH_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP3Resources: 1

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:0

## EXPECT
SEATCOUNT:4
P1RESAVAILABLE:1
P3RESAVAILABLE:0

---

# TeamSuns_KeepItOnYourOwnBoard
#// The other end of the same range — answering 1 takes it on your own board and leaves the teammate's
#// economy intact. Paired with the section above, this proves the answer is honoured rather than the
#// side being decided some fixed way.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: ASH_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP3Resources: 1

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:1

## EXPECT
SEATCOUNT:4
P1RESAVAILABLE:0
P3RESAVAILABLE:1

---

# TeamSuns_OnlyTheTeammateHasAReadyResource_NoPrompt
#// The degenerate edge: seat 1 has nothing ready, so the only legal answer is "all from the teammate"
#// and the engine must resolve it silently rather than ask a one-answer question.
#// Seat 1's single resource starts EXHAUSTED.

## GIVEN
CommonSetup: yyk/yyk
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: ASH_216:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Resources: 1:SOR_046:0
WithP3Resources: 1

## WHEN
- P1>AttackGroundArena:0:P2G0

## EXPECT
SEATCOUNT:4
P1NODECISION
P3RESAVAILABLE:0
