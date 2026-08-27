# Attack_InitiativeBuff
#// SHD_101 Adelphi Patrol Wing (5-cost space) — "When Played: You may attack with a unit. If you have the
#// initiative, it gets +2/+0 for this attack." With P1 holding the initiative, SOR_237 (2 power) attacks the
#// base at 4.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1Hand: SHD_101
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2BASEDMG:4

---

# Attack_NoInitiative_NoBuff
#// SHD_101 Adelphi Patrol Wing — without the initiative (P2 holds it), the attacking SOR_237 gets no +2 and
#// deals its printed 2 to the base.

## GIVEN
CommonSetup: ggw/ggw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_101
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2BASEDMG:2

---

# FourSeats_AFARSeatsInitiativeGrantsTheBuff
#// SHD_101 "If YOU have the initiative, it gets +2/+0 for this attack." Same hand-decoded counter as
#// SOR_163 — `strpos($ic,'P1') === 0 ? 1 : 2` — so above two seats a seat-3/4 initiative holder got no
#// bonus and seat 2 got one it had not earned. SEAT 3 holds the initiative and plays the Patrol Wing;
#// its 2-power SOR_237 must hit P2's base for 4, not 2. Now gated on PlayerHasIniative().
#// ⚠ P3 needs 9 resources, not 5: CommonSetup only dresses seats 1-2, so seat 3 pays the aspect penalty.

## GIVEN
CommonSetup: ggw/ggw
SkipPreGame: true
WithTeams: true
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithInitiativePlayer: 3
WithInitiativeClaimed: true
WithActivePlayer: 3
WithP3Resources: 9
WithP3Hand: SHD_101
WithP3SpaceArena: SOR_237:1:0

## WHEN
- P3>PlayHand:0
- P3>AnswerDecision:mySpaceArena-0
- P3>AnswerDecision:p2Base-0

## EXPECT
SEATCOUNT:4
P3HANDCOUNT:0
P2BASEDMG:4
