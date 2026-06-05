# SOR_053 Luke's Lightsaber — the heal+shield is conditional on the host being Luke Skywalker.
# Attached to Battlefield Marine (not Luke, pre-damaged 2), the upgrade still attaches (and
# grants +1/+3), but the When Played effect does nothing: the Marine keeps its 2 damage and
# gains no Shield. Absence guard for the "is attached unit Luke Skywalker" condition.

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
