## GIVEN
# Fleet Lieutenant: Heroism, cost 3
# Tarkin+Echo Base provides [Command, Villainy, Command] — no Heroism, +2 penalty → total 5
CommonSetup: ggk/grw/{myResources:4;handCardIds:SEC_251}

## WHEN
- P1>PlayHand:0

## EXPECT
# 4 resources is one short of base cost (3) + penalty (2) = 5 — play is blocked
P1HANDCOUNT:1
P1GROUNDARENACOUNT:0
P1RESAVAILABLE:4
