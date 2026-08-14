# AttachToLuke_HealAndShield
#// COVERAGE: offer=deferred pending an engine fix — the host pool should be every non-Vehicle
#//           unit on BOTH sides (CR 2.e) but is currently friendly-only and unfiltered (see the
#//           session log); every section here uses a single legal host so the attach auto-resolves ·
#//           reqboundary=HealAll_StacksWithExistingShield (shield play and saber play arrive as
#//           separate requests) · control=N/A pending the same fix (no enemy host reachable) ·
#//           boundary pair=AttachToLuke_HealAndShield + AttachToLukeLeaderUnit_ShieldEvenWithoutHeal
#//           (named-host gate on, incl. heal-0) vs AttachToNonLuke_NoEffect (gate off) ·
#//           decline=N/A (mandatory When Played; the attach itself has no decline).
#// SOR_053 Luke's Lightsaber (Upgrade, +1/+3) — Attach to a non-Vehicle unit. When Played: If
#// attached unit is Luke Skywalker, heal all damage from him and give him a Shield token.
#// P1 plays the Lightsaber; Luke (SOR_051, 6/7, pre-damaged 3) is the only valid host → it
#// auto-attaches, and because the host IS Luke Skywalker he is fully healed and shielded.
#// (Non-pilot upgrade → its When Played fires via the WhenPlayed fallback with the host mzID.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_053}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:3    # Luke Skywalker with 3 damage — only non-Vehicle host

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
#// Subcards = the Lightsaber + the Shield token (SOR_T02), so the raw upgrade/subcard count is 2.
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_053

---

# AttachToNonLuke_NoEffect
#// SOR_053 Luke's Lightsaber — the heal+shield is conditional on the host being Luke Skywalker.
#// Attached to Battlefield Marine (not Luke, pre-damaged 2), the upgrade still attaches (and
#// grants +1/+3), but the When Played effect does nothing: the Marine keeps its 2 damage and
#// gains no Shield. Absence guard for the "is attached unit Luke Skywalker" condition.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6;handCardIds:SOR_053}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2    # Battlefield Marine with 2 damage — non-Luke host

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# AttachToLukeLeaderUnit_ShieldEvenWithoutHeal
#// SOR_053 Luke's Lightsaber — "attached unit is Luke Skywalker" also matches the DEPLOYED Luke
#// leader unit (SOR_005), and the Shield rider is not contingent on healing anything: the
#// undamaged leader still gets his Shield token. Single legal host -> the attach auto-resolves.

## GIVEN
CommonSetup: bbw/ggk/{
  myResources:2;
  myLeader:SOR_005:1:1;
  myhandCardIds:SOR_053
}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_053
P1NODECISION

---

# HealAll_StacksWithExistingShield
#// SOR_053 Luke's Lightsaber — the heal+shield adds to what Luke already has: SOR_073 Moment of
#// Peace shields the damaged Luke JK first, then the Lightsaber heals ALL 3 damage and grants a
#// SECOND Shield (2 Shields + the saber = 3 subcards).

## GIVEN
CommonSetup: bbw/ggk/{
  myResources:3;
  myhandCardIds:SOR_073,SOR_053
}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:3

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:0:UPGRADECOUNT:3
P1NODECISION

---

# AttachPool_NonVehicle_BothSides
#// Candidate #10 fix guard: printed "Attach to a non-Vehicle unit" (no "friendly") — the pool is
#// non-Vehicles from all four arenas (CR 2.e enemy hosts legal) and EXCLUDES Vehicles. The TIE
#// Fighter must not be offered; the enemy Trooper must be. Offer left pending.

## GIVEN
CommonSetup: bbw/yyk
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_053
WithP1GroundArena: SOR_128:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
