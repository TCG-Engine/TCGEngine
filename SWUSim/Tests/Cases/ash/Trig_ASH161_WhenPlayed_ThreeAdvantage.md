# ASH_161 Zeb Orrelios (Ground, 5/7, cost 7) — When Played: give 3 Advantage tokens to another unit. Zeb
# enters and piles 3 Advantage onto SOR_095 (the only other unit, auto-resolved).
## GIVEN
CommonSetup: rrw/rrk/{myResources:7;handCardIds:ASH_161}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:3
