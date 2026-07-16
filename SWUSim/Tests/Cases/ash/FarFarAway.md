# ReturnFriendlyThenEnemy
#// ASH_236 Far Far Away (Event, cost 3) — Return a friendly non-leader unit to hand; if you do, return an
#// enemy non-leader unit to hand. SOR_095 (friendly, auto) and SEC_080 (enemy, auto) are each the only legal
#// target, so both are returned and the arenas empty.
## GIVEN
CommonSetup: yyk/yyk/{myResources:3;handCardIds:ASH_236}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
