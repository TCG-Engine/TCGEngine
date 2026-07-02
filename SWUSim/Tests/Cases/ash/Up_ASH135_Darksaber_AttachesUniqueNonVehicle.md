# ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Positive host case (guards over-blocking).
# Board has a valid host: LAW_139 Admiral Motti (unique=true, Imperial/Official, NO Vehicle trait).
# Darksaber is a legal fit → it attaches. Proves the restriction doesn't over-block a valid host.
# Darksaber is Command, cost 4 → ggw covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1GroundArena: LAW_139:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:ASH_135
