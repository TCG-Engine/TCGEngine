# ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Host-restriction (unique half).
# Board has ONLY a non-unique non-Vehicle unit (SOR_095 Battlefield Marine, unique=false) — it is a
# non-Vehicle, so this isolates the *unique* rule: the only reason it's an illegal host is that it
# isn't unique. Darksaber has no valid host → no-op, card stays in hand, the unit stays bare.
# Darksaber is Command, cost 4 → ggw covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
