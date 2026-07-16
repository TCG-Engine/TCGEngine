# AttachToLuke_HealAndShield
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
