# WhenPlayedOnLeia_Shield
#// LAW_111 Leia's Disguise (Upgrade, cost 2, Vigilance/Heroism) — "Attach to a non-Vehicle unit. ...
#// When Played: If attached unit is Leia Organa, give a Shield token to a friendly unit." Played onto
#// SOR_189 (Leia Organa) — the only friendly unit, so the shield auto-targets her.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_189:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_189
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# NoShieldOnNonLeia
#// LAW_111 Leia's Disguise — the "When Played: give a Shield" clause is conditional on the attached unit
#// being Leia Organa. Attached to SOR_095 Battlefield Marine (not Leia), no Shield is granted: the unit
#// just carries the disguise (1 upgrade, 0 shields).

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0
- P1>ChooseMyGroundUnit:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# AttachNonVehicleOnly
#// LAW_111 Leia's Disguise — "Attach to a non-Vehicle unit." A friendly Vehicle (SOR_232 AT-ST) is NOT a
#// legal host; only the non-Vehicle SOR_095 is selectable.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_232:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0

---

# OfferPool_AttachHostIsAnyNonVehicleEitherSide
#// LAW_111 Leia's Disguise — TRIAGE RESULT: PORTABLE. The existing AttachNonVehicleOnly section asserts
#// only the OUTCOME (which unit ended up wearing it) on an all-friendly board, so it cannot see the two
#// failure modes this pool actually has. This section asserts the pool itself.
#// "Attach to a NON-VEHICLE unit" names no controller, so per CR 2.e it spans BOTH sides: an ENEMY
#// non-Vehicle is a legal host and a FRIENDLY Vehicle is not. Discriminating board — friendly
#// non-Vehicle SOR_095 (IN), friendly Vehicle SOR_232 AT-ST (OUT), enemy non-Vehicle SEC_080 (IN),
#// enemy Vehicle SEC_213 (OUT). Two legal hosts keep the host MZCHOOSE from auto-resolving; the pick is
#// left UNANSWERED so the pool can be read.
#// COVERAGE: offer=OfferPool_AttachHostIsAnyNonVehicleEitherSide (attach pool: Vehicles out on both
#//           sides, enemy non-Vehicle in) + OfferPool_ShieldGoesToFriendlyUnitsOnly (the Shield pool is
#//           friendly-only and spans both arenas) · reqboundary=NOT COVERED (the "is the host Leia
#//           Organa" test reads the host object at resolution time; no section forces a
#//           SimulateRequestBoundary between the attach and the Shield) · control=NOT COVERED (an
#//           upgrade is controlled by whoever PLAYED it, so a Disguise on an enemy host would still let
#//           its owner pick the Shield target; no section attaches to an enemy and checks that) ·
#//           boundary pair=WhenPlayedOnLeia_Shield (host IS Leia → Shield) vs NoShieldOnNonLeia (host is
#//           not → no Shield) · decline=N/A (both clauses are mandatory — no "you may" in the text)

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
P1GROUNDARENAUNIT:1:CARDID:SOR_232
P2SPACEARENAUNIT:0:CARDID:SEC_213

---

# OfferPool_ShieldGoesToFriendlyUnitsOnly
#// LAW_111 Leia's Disguise — the SECOND pool, and the one the existing WhenPlayedOnLeia_Shield section
#// could not see because its board had exactly one friendly unit (so the Shield auto-targeted her).
#// "Give a Shield token to a FRIENDLY unit" IS controller-restricted and is NOT arena-restricted.
#// Discriminating board — Leia SOR_189 (IN), a second friendly ground unit SOR_095 (IN), a friendly
#// SPACE unit SOR_178 (IN, proving no arena narrowing), and an enemy ground unit SEC_080 (OUT). The
#// Disguise is attached to Leia explicitly so the Leia gate passes; the Shield pick is then left
#// UNANSWERED so the pool can be read.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SOR_189:1:0
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_178:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LAW_111

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&mySpaceArena-0
P1GROUNDARENAUNIT:0:CARDID:SOR_189
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
