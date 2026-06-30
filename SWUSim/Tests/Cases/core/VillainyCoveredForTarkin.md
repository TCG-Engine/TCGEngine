## GIVEN
# Cell Block Guard: Villainy, cost 3
# Tarkin+Echo Base provides [Command, Villainy, Command] — Villainy covered, 0 penalty
CommonSetup: ggk/grw/{myResources:3;handCardIds:SOR_229}

## WHEN
- P1>PlayHand:0

## EXPECT
# Plays at base cost 3 — no penalty, all resources spent
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
