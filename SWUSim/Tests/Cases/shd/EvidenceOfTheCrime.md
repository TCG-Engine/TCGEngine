# StealUpgradeAndReattach
#// SHD_077 Take Control (3-cost event, Vigilance) — "Take control of an upgrade that costs 3 or less and
#// attach it to an eligible unit of your choice." P1 takes SOR_120 (cost 3) off the enemy SEC_080 and
#// re-attaches it to the friendly SOR_095.
#// COVERAGE: offer=TWO offers, both asserted PENDING —
#//           Offer_UpgradesCostingThreeOrLessAcrossBothSides (which upgrade: every upgrade in play on
#//           either side, addressed as a subcard mzID, minus anything costing 4+) and
#//           HostOffer_TheMovedUpgradesOwnAttachRestrictionGatesTheHosts (which host: the moved
#//           upgrade's OWN printed attach restriction still applies) ·
#//           boundary=Offer_UpgradesCostingThreeOrLessAcrossBothSides pairs a cost-3 upgrade (in)
#//           against a cost-4 upgrade (out) on the "3 or less" threshold ·
#//           control=StealUpgradeAndReattach and EnemyUpgradeMovedToAFriendlyUnitInAnotherArena take an
#//           ENEMY-controlled upgrade onto a friendly host, and OwnUpgradeMovedOntoAnEnemyUnit pushes a
#//           friendly one the other way; an upgrade is friendly to its HOST's controller, so the host it
#//           ends up attached to IS the control assertion ·
#//           decline=N/A — SHD_077 has no "you may" (the text is an unconditional "take control of an
#//           upgrade … and attach it"); the generic move-upgrade helper's declinable pick belongs to
#//           that helper, not to a clause of this card ·
#//           reqboundary=N/A — the upgrade pick and the host pick are queued back-to-back inside one
#//           resolution and the host pool is rebuilt from the answer's own subcard address (no
#//           side-channel state that a serialization hop could drop).

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Offer_UpgradesCostingThreeOrLessAcrossBothSides
#// SHD_077 THE FIRST OFFER. "Take control of AN UPGRADE that costs 3 or less" — no friendly/enemy
#// qualifier, so the pool is every upgrade in play on either side, addressed as a subcard mzID
#// (<host>.u<sub>) so the pick carries its host. In: SHD_224 (c2) on P1's marine, SOR_120 (c2) on P2's
#// Dark Trooper, SOR_214 (c1) on P2's AT-RT. Out: TWI_236 (c4), the second upgrade on the Dark Trooper
#// — cost here is the PRINTED cost, and 4 is one over the line. Left PENDING so the offer is the
#// assertion.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SEC_080:1:0 SOR_249:1:0]
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:TWI_236
WithP2GroundArenaUpgrade: 1:SOR_214

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0.u0&theirGroundArena-0.u0&theirGroundArena-1.u0

---

# HostOffer_TheMovedUpgradesOwnAttachRestrictionGatesTheHosts
#// SHD_077 THE SECOND OFFER. "attach it to an ELIGIBLE unit of your choice" — eligible means the moved
#// upgrade's own printed attach restriction still has to be satisfied. SOR_214 Smuggling Compartment is
#// "Attach to a VEHICLE unit", so once it is the chosen upgrade the host pool collapses to the Vehicles:
#// P1's SOR_237 X-Wing and P2's SOR_178 Cartel Spacer. P1's SOR_095 marine (Rebel/Trooper) and P2's
#// SEC_080 Dark Trooper (Imperial/Droid/Trooper) are both units in play and both ineligible. Two legal
#// hosts keep the pick interactive so the pool can be read while the decision is still PENDING.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: [SOR_249:1:0 SEC_080:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# OwnUpgradeMovedOntoAnEnemyUnit
#// SHD_077 — the card never says the upgrade has to be the OPPONENT's, and it never says the host has to
#// be yours. Here P1 takes its OWN SHD_224 off its own marine and staples it onto the enemy SEC_080.
#// SHD_224 is "Attach to a non-Vehicle unit", so the host pool is the two non-Vehicle units other than
#// the source host (P1's SOR_128 and P2's SEC_080) — two options, so the pick is a real choice and P1
#// deliberately takes the enemy one. P2's SOR_249 AT-RT is a Vehicle and never eligible.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: [SOR_095:1:0 SOR_128:1:0]
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SEC_080:1:0 SOR_249:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_224
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# OwnUpgradeMovedOntoAnotherFriendlyUnit
#// SHD_077 — the same fixture as OwnUpgradeMovedOntoAnEnemyUnit with the OTHER host chosen, which is
#// what makes that section's choice load-bearing: the same upgrade can just as legally be shuffled onto
#// a second friendly unit. Both destinations are offered; only the answer differs.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: [SOR_095:1:0 SOR_128:1:0]
WithP1GroundArenaUpgrade: 0:SHD_224
WithP2GroundArena: [SEC_080:1:0 SOR_249:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0.u0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_128
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SHD_224
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1NODECISION

---

# EnemyUpgradeMovedToAFriendlyUnitInAnotherArena
#// SHD_077 — the sharp case: the upgrade crosses BOTH the control line and the arena line in one move.
#// SOR_214 Smuggling Compartment sits on P2's ground AT-RT; the only Vehicle left anywhere is P1's
#// SPACE X-Wing, so the eligible-host pool narrows to exactly one and the host pick auto-resolves onto
#// it — P1NODECISION plus the upgrade's landing spot IS the assertion here, and the two non-Vehicle
#// units on the board (P1's marine, P2's Dark Trooper) staying bare is the proof the restriction was
#// applied rather than the pool simply being small.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: [SOR_249:1:0 SEC_080:1:0]
WithP2GroundArenaUpgrade: 0:SOR_214

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_249
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_214
P1NODECISION

---

# StolenUpgrade_MayStayOnItsCurrentHost
#// SHD_077 — USER RULING (2026-08-15): the card says "attach it to an eligible unit of your choice", NOT
#// "another unit", so the upgrade's CURRENT host is a legal destination. That makes "take control of an
#// enemy upgrade and leave it exactly where it is" a real line of play — the take-control IS the effect.
#// P2's SOR_046 wears SOR_071 Electrostaff (cost 2); P1 takes control of it and re-attaches to the same
#// host, so the board looks unchanged while the upgrade has changed hands. The source host appearing in
#// its own destination offer is the load-bearing assertion.

## GIVEN
CommonSetup: bbw/bbw/{myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_077
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_071

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P1SELECTABLEHAS:theirGroundArena-0
