# CreatesThreeCreditTokens
#// LAW_248 Windfall (Event, cost 5, Cunning) — Create 3 Credit tokens.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Hand: LAW_248

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:3
P1RESCOUNT:5
P1NODECISION

---

# P2Seat_AllThreeCreditsGoToTheCASTER
#// LAW_248 Windfall — the three Credits belong to whoever resolved the event. P2 casts it: P2 holds 3 and
#// P1 holds none. The existing section is P1-only, so it cannot see a hardcoded seat.

## GIVEN
CommonSetup: rrk/yyw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 5
WithP2Hand: LAW_248

## WHEN
- P2>PlayHand:0

## EXPECT
P2CREDITCOUNT:3
P1CREDITCOUNT:0

---

# StacksOnTopOfCreditsAlreadyHeld
#// LAW_248 Windfall — "Create 3" is additive: starting from 2 Credits the player ends on 5, not on 3.
#// ⚠ Holding Credits also changes the flow — they can pay the cost-5 event itself — so the "spend Credits
#// on this cost?" choose is declined first, leaving the original 2 intact to be counted.

## GIVEN
CommonSetup: yyw/rrk/{myResources:5}
P1OnlyActions: true
WithP1Credits: 2
WithP1Hand: LAW_248

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1CREDITCOUNT:5
P1RESAVAILABLE:0

---

# ThreeIsExactlyThree_NotOnePerAspectOrPerResource
#// LAW_248 Windfall — the count is a flat 3 regardless of the board it is cast on. Cast with a wide board
#// (two friendly units, an enemy unit) and a resource row far bigger than the cost, the result is still
#// exactly 3 Credits — the number is printed, not derived from anything countable. Boundary partner of
#// LAW_244 Unmarked Credits' single Credit at the same seat.

## GIVEN
CommonSetup: yyw/rrk/{myResources:9}
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 SEC_080:1:0]
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_248

## WHEN
- P1>PlayHand:0

## EXPECT
P1CREDITCOUNT:3
P1RESAVAILABLE:4
