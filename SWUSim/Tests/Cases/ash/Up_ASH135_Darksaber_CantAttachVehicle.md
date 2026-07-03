# ASH_135 The Darksaber — "Attach to a <uq> non-Vehicle unit." Host-restriction (Vehicle half).
# Board has ONLY a unique Vehicle (SOR_089, unique Imperial Capital Ship) — it IS unique, so this
# isolates the *non-Vehicle* rule: the only reason it's an illegal host is its Vehicle trait.
# Darksaber has no valid host → the play is a no-op, the card stays in hand, the Vehicle stays bare.
# Darksaber is Command, cost 4 → ggw (Command base + Command/Heroism leader) covers it, 4 resources.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4;handCardIds:ASH_135}
P1OnlyActions: true
WithP1SpaceArena: SOR_089:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
P1RESAVAILABLE:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
