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
