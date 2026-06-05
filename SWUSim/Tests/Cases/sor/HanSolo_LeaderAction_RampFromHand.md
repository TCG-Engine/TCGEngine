# SOR_017 Han Solo "Audacious Smuggler" — Leader Action [exhaust]:
# "Put a card from your hand into play as a resource and ready it."
# One hand card (SOR_095) auto-resolves → becomes a READY resource. Han exhausts.
# Resources go 3 → 4, all 4 ready (the new one entered READY, not exhausted).

## GIVEN
P1LeaderBase: SOR_017/SOR_024
P2LeaderBase: SOR_014/SOR_024
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_095
WithP1Resources: 3

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1RESCOUNT:4
P1RESAVAILABLE:4
P1HANDCOUNT:0
