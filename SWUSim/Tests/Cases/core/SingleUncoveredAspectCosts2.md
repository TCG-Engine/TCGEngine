## GIVEN
# Cell Block Guard: Villainy, cost 3
# Sabine+Echo Base provides [Aggression, Heroism, Command] — no Villainy, +2 penalty → total 5
CommonSetup: grw/ggk/{myResources:4;handCardIds:SOR_229}

## WHEN
- P1>PlayHand:0

## EXPECT
# 4 resources is one short of base cost (3) + penalty (2) = 5 — play is blocked
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4
