# NoInitiative_NoBuff
#// SOR_161 Ardent Sympathizer (3/3) — P2 holds the initiative, so P1's Ardent
#// Sympathizer does NOT get +2/+0. Reads its printed 3/3.
#// (Absence guard — passes pre-implementation; stays meaningful once the buff exists.)
#// COVERAGE: offer=N/A (a static while-condition self-buff — no target, no decision) ·
#//           decline=N/A (not optional) · control=OpponentCopy_NotBuffedByYourInitiative (the
#//           condition is read per CONTROLLER, so only the initiative-holder's copy is buffed) ·
#//           boundary=NoInitiative_NoBuff vs WithInitiative_Buffed (token with P2 vs with P1) and
#//           InitiativeUnclaimed_StillBuffed (the UNCLAIMED half of the holder state) ·
#//           reqboundary=N/A (recomputed on every stat read; no decision spans a request)

## GIVEN
CommonSetup: rrw/rrw
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: SOR_161:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# WithInitiative_Buffed
#// SOR_161 Ardent Sympathizer (3/3) — "While you have the initiative, this unit
#// gets +2/+0." P1 holds claimed initiative → reads 5/3.

## GIVEN
CommonSetup: rrw/rrw
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_161:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3

---

# InitiativeUnclaimed_StillBuffed
#// SOR_161 Ardent Sympathizer — "While you HAVE the initiative": holding the initiative token is
#// what matters, not whether it has been CLAIMED yet. P1 holds it UNCLAIMED (the state every round
#// opens in), so the Sympathizer is already 5/3. Boundary against WithInitiative_Buffed, which
#// pins the CLAIMED half of the same holder state, and against NoInitiative_NoBuff, where the
#// token sits with P2.

## GIVEN
CommonSetup: rrw/rrw
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1GroundArena: SOR_161:1:0

## WHEN

## EXPECT
INITIATIVECOUNTER:P1_UNCLAIMED
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3

---

# OpponentCopy_NotBuffedByYourInitiative
#// SOR_161 Ardent Sympathizer — "while YOU have the initiative" is read per CONTROLLER, not
#// globally. P1 holds claimed initiative and both players control a copy: P1's reads 5/3 while
#// P2's reads its printed 3/3 in the very same game state. (A global "someone has the
#// initiative" test would light up both.)

## GIVEN
CommonSetup: rrw/rrw
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_161:1:0
WithP2GroundArena: SOR_161:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:3
