# DefeatedThisPhase_Resource
#// SHD_105 Spark of Hope (2-cost Command/Heroism event) — "Choose a unit in your discard pile. If it was
#// defeated this phase, put it into play as a resource." P1's SEC_080 (3/3) attacks a Wampa (SOR_164,
#// 4/5): deals 3 (Wampa survives), Wampa counters 4 → SEC_080 dies THIS phase → discard (marked). P1
#// then plays Spark of Hope and picks it → it becomes a resource (exhausted). Resources: 6 start, 2
#// spent on the event = 4 available, +1 exhausted (SEC_080) = 7 total; discard holds only the event.
#// COVERAGE: offer=DefeatedThisPhase_Resource answers the discard picker explicitly; the filter behind it
#//           is proved by its two negatives, which both leave NO decision at all —
#//           NotDefeatedThisPhase_NoOp (a unit that was never defeated) and
#//           DefeatedPreviousPhase_MarkerClearedAtPhaseEnd (a unit defeated one phase too early) ·
#//           decline=N/A ("Choose a unit in your discard pile" is mandatory; a lone legal choice
#//           auto-resolves and an empty pool raises no prompt, so there is no refusal branch) ·
#//           boundary=the "this phase" window: DefeatedThisPhase_Resource (defeated in the current phase
#//           → ramped) vs DefeatedPreviousPhase_MarkerClearedAtPhaseEnd (the SAME kill, one regroup later
#//           → not eligible) · control=N/A (the card moves from the caster's own discard into the
#//           caster's own resource zone; no other player's object is touched) ·
#//           reqboundary=DefeatedPreviousPhase_MarkerClearedAtPhaseEnd drives a full pass/regroup/resource
#//           cycle between the defeat and the event, so the defeated-this-phase bookkeeping is read back
#//           after a phase transition rather than within one action.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SHD_105
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1GROUNDARENACOUNT:0
P1RESCOUNT:7
P1RESAVAILABLE:4
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_105

---

# NotDefeatedThisPhase_NoOp
#// SHD_105 Spark of Hope — the "defeated this phase" gate. A unit sitting in the discard that was NOT
#// defeated this phase (seeded directly) is not a valid target: the event resolves with no choice, no
#// resource ramp. Discard keeps SEC_080 + the played event; resources unchanged.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_105
WithP1Discard: SEC_080

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1RESCOUNT:6
P1DISCARDCOUNT:2

---

# DefeatedPreviousPhase_MarkerClearedAtPhaseEnd
#// SHD_105 Spark of Hope — the "this phase" window really is a PHASE window, not a game-long one. Same
#// kill as DefeatedThisPhase_Resource (SEC_080 3/3 attacks SOR_164 Wampa 4/5, takes 4 back and dies), but
#// both players then pass through the regroup phase before Spark of Hope is played. The defeated-this-
#// phase marker is cleared at the phase boundary, so SEC_080 is no longer an eligible target: the event
#// resolves with no decision and no ramp. Resources stay at 6 total / 4 available after paying the
#// event's 2 (the resource step is declined), and the discard holds SEC_080 plus the spent event.
#// Boundary partner of DefeatedThisPhase_Resource; NotDefeatedThisPhase_NoOp covers the never-defeated
#// case, which never sets the marker at all.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;theirResources:6}
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SHD_105
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046]
WithP2Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0

## EXPECT
PHASE:MAIN
P1NODECISION
P1GROUNDARENACOUNT:0
P1RESCOUNT:6
P1RESAVAILABLE:4
P1DISCARDCOUNT:2
P1DISCARDUNIT:0:CARDID:SEC_080
P1DISCARDUNIT:1:CARDID:SHD_105
