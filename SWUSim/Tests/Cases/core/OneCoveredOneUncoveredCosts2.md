## GIVEN
# Leia Organa: Cunning + Heroism, cost 2
# Sabine+Echo Base provides [Aggression, Heroism, Command] — Heroism covered, Cunning not → +2 penalty → total 4
CommonSetup: grw/ggk/{myResources:3;handCardIds:SOR_189}

## WHEN
- P1>PlayHand:0

## EXPECT
# 3 resources is one short of base cost (2) + penalty (2) = 4 — play is blocked
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:3
