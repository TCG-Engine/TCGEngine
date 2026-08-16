# PlayedFromHand_NoAttackAndHostStaysReady
#// SHD_174 Hotshot DL-44 Blaster (Upgrade, cost 1, Aggression, +2/+0) — "Attach to a non-VEHICLE unit.
#// Smuggle [3 resources, Cunning]. When played using Smuggle: Attack with attached unit."
#// The granted attack is gated on the SMUGGLE play path only. Played normally out of hand it must be a
#// plain upgrade: SOR_095 goes from 3/3 to 5/3, stays READY (it never attacked, so it never exhausted),
#// the enemy base is untouched and no decision is left pending. P1's only other unit is the SHD_111
#// Starhopper, a VEHICLE, so the marine is the single legal host and the attach auto-resolves.
#//
#// COVERAGE: offer=AttachOffer_OnlyNonVehicleUnitsEitherSide (host pool left PENDING and asserted with
#//   P1SELECTABLEEXACT — Vehicles excluded in BOTH arenas and on BOTH sides, enemy non-Vehicles in) ·
#//   reqboundary=Smuggle_AttackTargetIsChosenAndHostTakesDamageBack (the host mzID is handed from the
#//   payment step to the attach step to the granted attack across two decision boundaries) ·
#//   control=PlayedFromHand_AttachesToAnEnemyUnit (P1's upgrade buffs a unit P2 controls; the +2/+0
#//   resolves under the HOST's controller, not the owner's) · boundary=this section vs
#//   Smuggle_AttacksTheBaseWithTheAttachedUnit is the dispatch-path pair (identical card, hand play does
#//   NOT attack / Smuggle play DOES), and Smuggle_OffAspectSurchargeMakesItUnaffordable vs
#//   Smuggle_OffAspectPaidInFull_StillAttacks is the cost boundary either side of 5 ·
#//   decline=N/A — neither clause is a "you may"; the nearest refusal-shaped branch is a host that
#//   cannot attack (Smuggle_ExhaustedHostCannotAttack), which closes with no prompt at all.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_174
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SHD_111:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_174
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:0:READY
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2BASEDMG:0
P1HANDCOUNT:0
P1RESAVAILABLE:2
P1NODECISION

---

# AttachOffer_OnlyNonVehicleUnitsEitherSide
#// THE OFFER AXIS for "Attach to a non-VEHICLE unit." The restriction has no "friendly" qualifier, so
#// the legal-host pool is every non-Vehicle unit in play on EITHER side, and Vehicles are out no matter
#// whose they are or which arena they sit in. Board: P1's SOR_095 (legal), P1's SOR_232 AT-ST (Vehicle,
#// ground), P1's SHD_111 Starhopper (Vehicle, space) and P2's SOR_046 (legal). The pool must be exactly
#// the two non-Vehicles. Two legal hosts also stop the pick auto-resolving, so it can be read while
#// still PENDING — the decision is deliberately left unanswered.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_174
WithP1GroundArena: [SOR_095:1:0 SOR_232:1:0]
WithP1SpaceArena: SHD_111:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# PlayedFromHand_AttachesToAnEnemyUnit
#// The control-change axis. "Attach to a non-VEHICLE unit" names no controller, so an enemy unit is a
#// legal host (CR 2.e) and the +2/+0 is applied under the host's controller. P1's only unit is a Vehicle,
#// leaving P2's SOR_095 as the single legal host: it auto-attaches there and P2's marine becomes 5/3
#// while P1's own AT-ST stays bare. Nothing attacks — the granted attack is a Smuggle-only clause, and
#// it must not fire just because the upgrade landed somewhere.

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SHD_174
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_174
P2GROUNDARENAUNIT:0:POWER:5
P2GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1BASEDMG:0
P2BASEDMG:0
P1RESAVAILABLE:2
P1NODECISION

---

# Smuggle_AttacksTheBaseWithTheAttachedUnit
#// THE DISPATCH-PATH TWIN of PlayedFromHand_NoAttackAndHostStaysReady. Same card, same host, played
#// out of the RESOURCE row instead of hand — and now "When played using Smuggle: Attack with attached
#// unit" fires. Smuggle is 3 with Cunning covered by the yyw base; the smuggled card is itself a ready
#// resource and exhausts toward its own cost (CR 8.22.e), so only 2 of the other 3 are spent and 1 stays
#// ready. The spent slot is replaced from the top of the deck, so the resource COUNT is still 4.
#// The buff is applied before the attack: 3 + 2 = 5 to the base, and the marine ends EXHAUSTED.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_174
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:4
P1RESAVAILABLE:1
P1DECKCOUNT:0
P1NODECISION

---

# Smuggle_AttackTargetIsChosenAndHostTakesDamageBack
#// The granted attack is a REAL attack, not a base burn — with an enemy unit on the board the defender
#// is a genuine choice, and picking it means taking damage back. This is also the request-boundary case:
#// the host is chosen at one decision, the attack target at the next, and the host mzID has to survive
#// both hops for the right unit to swing. P1's SOR_046 (3/7) is the host, so the pool for the attach is
#// two units (its own and P2's SOR_095) and must be answered explicitly. Buffed to 5/7 it kills the 3/3
#// marine outright and takes 3 back.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:0
P1NODECISION

---

# Smuggle_ExhaustedHostCannotAttack
#// The "attached unit cannot attack" branch. Only a ready unit may attack, so smuggling the blaster onto
#// an already-EXHAUSTED marine attaches it and then stops — no attack, no damage, and critically no
#// stuck action: the resource is still consumed and replaced, and the turn closes cleanly with no
#// pending decision. A granted attack that cannot happen must not leave the action half-open.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P2BASEDMG:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_174
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:4
P1DECKCOUNT:0
P1NODECISION

---

# Smuggle_OffAspectSurchargeMakesItUnaffordable
#// Smuggle [3 resources, Cunning] is an ASPECTED cost, so an uncovered aspect adds the usual +2. Under a
#// Command base and leader the real price is 5, above the 4 resources available (3 others + the card
#// self-paying), and the play must roll back COMPLETELY rather than half-spending: the blaster is still
#// a resource, every resource is still READY, the deck is untouched, nothing attaches and nothing
#// attacks. The silent-no-op shape is exactly what an unaffordable play looks like, so the assertions
#// have to pin the untouched state rather than just the absence of the upgrade.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:0
P1RESCOUNT:4
P1RESAVAILABLE:4
P1DECKCOUNT:1
P1NODECISION

---

# Smuggle_OffAspectPaidInFull_StillAttacks
#// The other side of that boundary: same off-aspect Command board, but now 6 resources, so the
#// surcharged Smuggle of 5 IS payable and everything proceeds normally — the blaster attaches and the
#// granted attack fires for 5. Proves the section above failed on PRICE and not because an off-aspect
#// Smuggle silently loses the "when played using Smuggle" clause. 6 resources, 5 spent (1 of them the
#// card itself), 1 ready, and the spent slot replaced from the deck keeps the count at 6.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Resources: 5:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:5

## EXPECT
P2BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_174
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:6
P1RESAVAILABLE:1
P1DECKCOUNT:0
P1NODECISION

---

# Smuggle_NoLegalHost_IsACompleteNoOp
#// The attach restriction gates the SMUGGLE play too, and it has to be checked BEFORE anything is paid.
#// P1's only unit is a Vehicle and P2 has none, so there is no legal host anywhere: the Smuggle must be
#// refused outright with nothing spent — blaster still in the resource row, all four resources still
#// ready, deck untouched. Paying first and discovering the problem afterwards would strand the card.

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:READY
P2BASEDMG:0
P1RESCOUNT:4
P1RESAVAILABLE:4
P1DECKCOUNT:1
P1NODECISION
