# WhenPlayedDamage3Ground
#// LAW_187 "Staccato Lightning" Repeater (Upgrade, +3/+1) — "When Played: Deal 1 damage to each of up to
#// 3 different ground units." The attach pool is every non-Vehicle unit on EITHER side (CR 2.e), so
#// the host is chosen explicitly: P1's SEC_080.  P1 deals 1 to each of P2's three SOR_128 (3/1),
#// defeating all three.

## GIVEN
CommonSetup: brk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1Hand: LAW_187
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:0

---

# OfferPool_AttachHostIsAnyNonVehicleEitherSide
#// LAW_187 "Staccato Lightning" Repeater — offer assertion for the printed attach restriction, "Attach to
#// a NON-VEHICLE unit". The restriction names no controller, so per CR 2.e it spans BOTH sides: an ENEMY
#// non-Vehicle is a legal host and a FRIENDLY Vehicle is not. Discriminating board — friendly non-Vehicle
#// SEC_080 (IN), friendly Vehicle SOR_232 AT-ST (OUT), enemy non-Vehicle SOR_046 (IN), enemy Vehicle
#// SEC_213 (OUT, and also proves the pool is not silently arena-limited by accident). Two legal hosts
#// keep the host MZCHOOSE from auto-resolving; the pick is left UNANSWERED so the pool can be read. This
#// is the exact pool shape whose two failure modes — offering a Vehicle, and omitting enemy units — have
#// both been real bugs in this upgrade family.

## GIVEN
CommonSetup: brk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_187

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P2SPACEARENAUNIT:0:CARDID:SEC_213

---

# OfferPool_DamageHitsGroundUnitsOnly
#// LAW_187 "Staccato Lightning" Repeater — offer assertion for the SECOND pool, "When Played: Deal 1
#// damage to each of up to 3 different GROUND units". Distinct from the attach pool above: this one is
#// arena-restricted but NOT controller-restricted and NOT type-restricted (a Vehicle ground unit would
#// be a legal damage target even though it is an illegal host). Discriminating board — a friendly SPACE
#// unit (SOR_178) and an enemy SPACE unit (SEC_213) must both be OUT of the damage pool, while the
#// friendly and enemy GROUND units must both be IN. The host is chosen explicitly (myGroundArena-0), then
#// the MZMULTICHOOSE is left UNANSWERED so its candidate list can be read; the harness strips the
#// "min|max|" prefix, so this asserts the candidate set, not the 0..3 bounds.
#// COVERAGE: offer=OfferPool_AttachHostIsAnyNonVehicleEitherSide (attach pool: Vehicle out, enemy
#//           non-Vehicle in) + OfferPool_DamageHitsGroundUnitsOnly (damage pool: space out, both sides
#//           in) · reqboundary=NOT COVERED (the chosen mzIDs are re-resolved to UniqueIDs inside the
#//           handler before any damage lands, which is the reindex guard, but no section forces a
#//           SimulateRequestBoundary across the multi-pick) · control=N/A (one-shot damage) ·
#//           boundary pair=WhenPlayedDamage3Ground (exactly 3 picked, all lethal) vs this section's
#//           pool-only read · decline=NOT COVERED ("up to 3" means 0 is legal — the multi-choose min is
#//           0 — but no section picks zero)

## GIVEN
CommonSetup: brk/rrk/{myResources:8}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_187

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P1SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENAUNIT:0:CARDID:SEC_213
