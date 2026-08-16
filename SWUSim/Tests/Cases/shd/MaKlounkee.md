# NoUnderworld_Fizzle
#// SHD_229 Ma Klounkee — with no friendly non-leader Underworld unit to return, the effect fizzles: no
#// bounce, no damage. The event still lands in the discard.
#// COVERAGE: offer=BounceOffer_OnlyFriendlyNonLeaderUnderworldUnits (bounce pool, pending) +
#//           DamageOffer_AnyUnitEitherSideIncludingDeployedLeaders (damage pool, pending) — the two
#//           pools are deliberately different and both are asserted · boundary pair=the bounce filter's
#//           three legs are each paired with an included body in the same fixture (friendly UW ground /
#//           friendly UW space are IN; friendly non-UW, friendly UW LEADER and enemy UW are OUT) ·
#//           control=N/A (both clauses are resolved entirely by the caster; nothing on this card reads
#//           or changes control, and the bounce already sends the unit to its OWNER's hand — covered
#//           generically by the bounce helper) · reqboundary=
#//           SimulateRequestBoundary_DamageResolvesAfterTheBounce (the bounce pick and the damage pick
#//           are separate requests; the "if you do" gate is re-read from the serialized gamestate) ·
#//           decline=N/A (neither clause is a "you may" — the bounce is a mandatory choose and the
#//           damage is gated only on the bounce happening)

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1
P1NODECISION

---

# ReturnUnderworld_Deal3
#// SHD_229 Ma Klounkee (1-cost event, Cunning) — "Return a friendly non-leader Underworld unit to its
#// owner's hand. If you do, deal 3 damage to a unit." The friendly LAW_124 (Underworld) is returned to P1's
#// hand, then P1 deals 3 to the enemy SOR_046 (7 HP → 3 damage).

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# BounceOffer_OnlyFriendlyNonLeaderUnderworldUnits
#// SHD_229 — the bounce clause is triple-filtered: FRIENDLY, NON-LEADER, and Underworld. The board seats
#// one of each failing case alongside the two that qualify — a friendly non-Underworld ground unit
#// (SOR_095, Rebel/Trooper), a friendly Underworld unit that is a LEADER (SOR_015 Boba Fett deployed),
#// and an enemy Underworld space unit (SOR_209) — so only the friendly Underworld ground unit (SOR_247)
#// and the friendly Underworld space unit (SHD_152) may be returned. The pick is left PENDING so the
#// offer itself is the assertion. Deployed leaders seat at the END of the ground arena, so P1's leader
#// is myGroundArena-2.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;myLeader:SOR_015:1:1;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: SOR_247:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SHD_152:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_209:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0

---

# DamageOffer_AnyUnitEitherSideIncludingDeployedLeaders
#// SHD_229 — the SECOND clause is not filtered at all: "deal 3 damage to A UNIT" spans both sides, both
#// arenas, and includes deployed leader units. Same fixture as the bounce-offer section with the bounce
#// already spent on SOR_247 (myGroundArena-0) and the damage pick left PENDING. After the bounce the
#// ground arena compacts, so P1's ground is SOR_095 then P1's deployed leader, and P2's is SOR_046 then
#// P2's deployed leader.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1;myLeader:SOR_015:1:1;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: SOR_247:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SHD_152:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_209:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# DamageToFriendlyUnit_EverythingAutoResolves
#// SHD_229 — the damage may be aimed at a FRIENDLY unit, and when each clause has exactly one legal
#// choice the whole card resolves with no prompt at all. SOR_247 is the only friendly Underworld unit,
#// so the bounce auto-targets it; once it is in hand the only unit left anywhere is P1's own SOR_046, so
#// the 3 damage auto-lands on it (3/7 → 3 damage, survives). Auto-resolution IS the assertion here.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: SOR_247:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1HANDCOUNT:1
P1HANDCARD:0:SOR_247

---

# BounceOnlyAvailable_NothingHappensAfterIt
#// SHD_229 — the bounce can empty the board of every legal damage target. SOR_247 is the only unit in
#// play on either side: it is returned to hand, and the "if you do" damage clause then has nothing to
#// aim at, so it silently finds no target rather than prompting or erroring. The event still reaches the
#// discard and the resource is still spent.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: SOR_247:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_247
P1DISCARDCOUNT:1
P1RESAVAILABLE:0

---

# NoUnderworld_ResourcesStillSpent
#// SHD_229 — an event with no legal target still costs you the play. With only a non-Underworld friendly
#// unit on board the whole ability fizzles, but the 1 resource is still exhausted and the event still
#// goes to the discard. Companion to NoUnderworld_Fizzle, which asserts the board half of the same
#// scenario.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_229
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# SimulateRequestBoundary_DamageResolvesAfterTheBounce
#// SHD_229 — in production the bounce pick and the damage pick arrive as two separate requests, so the
#// "if you do" gate and the damage target pool both have to be re-read from the serialized gamestate.
#// Mirrors ReturnUnderworld_Deal3 with two friendly Underworld units (so the bounce is a real choice)
#// and the boundary inserted between the two answers.

## GIVEN
CommonSetup: yyk/yyk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SHD_229
WithP1GroundArena: SOR_247:1:0
WithP1SpaceArena: SHD_152:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1HANDCOUNT:1
P1HANDCARD:0:SOR_247
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
