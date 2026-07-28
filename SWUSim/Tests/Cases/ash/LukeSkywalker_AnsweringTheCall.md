# AoeIfFourUnits
#// ASH_112 Luke Skywalker (Ground, 5/5, Restore 1) — When Played: if you control at least 4 units, deal 3
#// damage to each enemy unit. P1 controls 3 units + Luke = 4, so each enemy (SEC_080 3/3, SOR_225 2/1)
#// takes 3 and is defeated.
## GIVEN
CommonSetup: ggw/ggk/{myResources:6;handCardIds:ASH_112}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0

---

# NoAoeUnderFour
#// ASH_112 Luke Skywalker — the AoE fires ONLY with 4+ friendly units. P1 controls 1 unit + Luke = 2
#// (< 4), so the enemy SEC_080 and SOR_225 take no damage and survive.
## GIVEN
CommonSetup: ggw/ggk/{myResources:6;handCardIds:ASH_112}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1
P2SPACEARENACOUNT:1

---

# AoeDealsExactlyThree_FriendlyUnaffected
#// ASH_112 Luke Skywalker — the AoE deals EXACTLY 3 to each enemy unit and never touches friendly units.
#// P1 controls 3 units + Luke = 4, so both enemies take 3 (each has 7 HP and survives at 3 damage) while
#// every friendly unit — including Luke — takes 0.
## GIVEN
CommonSetup: ggw/ggk/{myResources:6;handCardIds:ASH_112}
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_153:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:CARDID:JTL_153
P2SPACEARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:2:CARDID:ASH_112
P1GROUNDARENAUNIT:2:DAMAGE:0
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
