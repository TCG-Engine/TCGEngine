# Exhaust2EnemyResources
#// SEC_235 The Wrong Ride (event, cost 3) — Exhaust 2 enemy resources. P2 has 4 ready resources → 2
#//   after.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2Resources: 4:SOR_046:1
WithP1Hand: SEC_235

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESAVAILABLE:2

---

# OppHasNoReadyResources_NoEffect
#// SEC_235 The Wrong Ride — if the opponent has no ready resources there is nothing to exhaust; the event
#//   simply resolves to P1's discard. P2 has 3 resources, all already exhausted.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2Resources: 3:SOR_046:0
WithP1Hand: SEC_235

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESAVAILABLE:0
P2RESCOUNT:3
P1DISCARDCOUNT:1
P1HANDCOUNT:0

---

# OppHasFewerThanTwoReady_ExhaustsWhatsAvailable
#// SEC_235 The Wrong Ride — "Exhaust 2 enemy resources" is an up-to-2 exhaust: with only 1 ready resource
#//   the one ready resource is exhausted (not all-or-nothing). P2 has exactly 1 ready resource → 0 after.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2Resources: 1:SOR_046:1
WithP1Hand: SEC_235

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESAVAILABLE:0
P2RESCOUNT:1

---

# OpponentHasExactlyOneResource_ExhaustsThatOne
#// SEC_235 The Wrong Ride — "exhaust 2" is an upper bound. With only ONE resource in play (ready), that
#// single resource is exhausted and the event resolves cleanly rather than fizzling for want of a
#// second target.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
P1OnlyActions: true
WithP2Resources: 1:SOR_046:1
WithP1Hand: SEC_235

## WHEN
- P1>PlayHand:0

## EXPECT
P2RESAVAILABLE:0
P2RESCOUNT:1
P1DISCARDCOUNT:1
P1NODECISION

---

# PlayedViaPlot_StillExhaustsTwo
#// SEC_235 The Wrong Ride — it carries Plot, so it can be played out of the resource row when a leader
#// deploys, and the exhaust-2 resolves exactly as from hand. P2 has 4 ready resources → 2. P1's own row
#// stays at 8 (the played card is replaced from the top of the deck) with the 3 cost exhausted.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 1:SEC_235:1,7:SOR_046:1
WithP2Resources: 4:SOR_046:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P2RESAVAILABLE:2
P1RESCOUNT:8
P1RESAVAILABLE:5
P1DECKCOUNT:1

---

# TwinSuns_ExhaustsTheOPPONENTYOUCHOSE
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "Exhaust 2 ENEMY resources" names no seat.
#// It always hit OtherPlayer($player). Both seat 2 and seat 4 hold four ready resources; P1 picks seat 4,
#// which drops to 2 ready while seat 2 stays untouched at 4. Deliberately identical boards so the ONLY
#// difference the assertions can detect is which seat the pick named.
## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 3
WithP1Hand: SEC_235
WithP2Resources: 4:SOR_046:1
WithP4Resources: 4:SOR_046:1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P4
## EXPECT
SEATCOUNT:4
P4RESAVAILABLE:2
P2RESAVAILABLE:4
