# SOR_177 Bib Fortuna — the Action plays only an EVENT (not a unit). Here the hand holds
# a UNIT (SOR_095, Battlefield Marine), no events, with resources to spare. The action has
# no legal play, so it is a full no-op: Bib stays READY (action not spent), the unit stays
# in hand, resources unchanged, no decision pending. Guards the event-only type filter
# (distinguishing Bib from Alliance Dispatcher SOR_093, which plays a unit).

## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:SEC_080}
P1OnlyActions: true
WithP1GroundArena: SOR_177:1:0    # Bib Fortuna (ready) — index 0

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:READY
P1HANDCOUNT:1
P1RESAVAILABLE:3
P1NODECISION
