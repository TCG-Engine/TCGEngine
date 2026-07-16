# DefeatedThisPhase_Resource
#// SHD_105 Spark of Hope (2-cost Command/Heroism event) — "Choose a unit in your discard pile. If it was
#// defeated this phase, put it into play as a resource." P1's SEC_080 (3/3) attacks a Wampa (SOR_164,
#// 4/5): deals 3 (Wampa survives), Wampa counters 4 → SEC_080 dies THIS phase → discard (marked). P1
#// then plays Spark of Hope and picks it → it becomes a resource (exhausted). Resources: 6 start, 2
#// spent on the event = 4 available, +1 exhausted (SEC_080) = 7 total; discard holds only the event.

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
