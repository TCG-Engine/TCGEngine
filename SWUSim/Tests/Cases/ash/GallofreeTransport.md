# WhenDefeated_TwoToFriendly
#// ASH_254 Gallofree Transport (Space, 3/5) — When Defeated: give 2 Advantage tokens to a friendly unit.
#// Pre-damaged to 1 HP, ASH_254 attacks SOR_225 (2/1) and dies to the counter; its WhenDefeated gives 2
#// Advantage tokens to the surviving friendly unit (SOR_237, which reindexes to space-0 after ASH_254 dies).
## GIVEN
CommonSetup: yyw/yyk
WithP1SpaceArena: ASH_254:1:4
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:2
