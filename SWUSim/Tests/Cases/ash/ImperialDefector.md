# LookAtOppHand
#// ASH_250 Imperial Defector (Ground, 3/2, cost 2) — When Played: look at an opponent's hand. Playing the
#// Defector logs a private reveal of P2's hand to P1 (the only observable effect of this information ability).
## GIVEN
CommonSetup: bbw/bbk/{myResources:2;handCardIds:ASH_250;theirHandCardIds:SOR_095}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
LOGCONTAINS:looked at
