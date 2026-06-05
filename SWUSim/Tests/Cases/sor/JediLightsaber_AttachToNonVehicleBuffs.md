# SOR_054 Jedi Lightsaber — Upgrade (+3/+3), "Attach to a non-VEHICLE unit."
# P1 has a Vehicle (AT-AT idx 0) and a non-Vehicle (Battlefield Marine idx 1).
# The Vehicle is filtered out, so the only valid target is the Marine → auto-attach.
# Marine becomes 3+3 / 3+3 = 6/6 with one upgrade; the Vehicle is untouched.

## GIVEN
CommonSetup: bbw/bbw/{myResources:3;handCardIds:SOR_054}
WithP1GroundArena: SOR_148:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:CARDID:SOR_148
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADE:0:CARDID:SOR_054
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:1:HP:6
