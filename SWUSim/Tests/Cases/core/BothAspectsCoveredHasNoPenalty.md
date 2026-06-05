## GIVEN
# Battlefield Marine: Command + Heroism, cost 2
# Sabine+Echo Base provides [Aggression, Heroism, Command] — both aspects covered, 0 penalty
CommonSetup: grw/ggk/{myResources:2;handCardIds:SOR_095}

## WHEN
- P1>PlayHand:0

## EXPECT
# Plays at base cost 2 — no penalty, all resources spent
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
