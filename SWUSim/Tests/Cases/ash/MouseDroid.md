# NextImperialCheaper
#// ASH_237 Mouse Droid (Ground, 1/1, Raid 1) — When Played: the next Imperial unit you play this phase
#// costs 1 resource less. P1 plays Mouse Droid (cost 1), then plays SEC_080 (Imperial, cost 2) for 1: 2 - 1
#// (Mouse) - 1 (SEC_080) = 0 resources left.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:ASH_237,SEC_080}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:1:CARDID:SEC_080

---

# Raid1_WhileAttacking
#// ASH_237 Mouse Droid — Raid 1 gives +1/+0 while attacking. Mouse Droid (1 power) attacks P2's base for
#// 1 + 1 = 2.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_237:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:2

---

# NextDiscountSkipsImperialEvent
#// ASH_237 Mouse Droid — "the next Imperial UNIT you play this phase costs 1 less" applies only to units,
#// not events. After Mouse Droid, playing the Imperial event SOR_091 (The Emperor's Legion, cost 2) gets no
#// discount: 3 - 1 (Mouse Droid) - 2 (event at full price) = 0 resources left.
## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:ASH_237,SOR_091}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:0

---

# NextDiscountExpiresNextPhase
#// ASH_237 Mouse Droid — the "next Imperial unit costs 1 less" is only for THIS phase. P1 plays Mouse Droid
#// (arming the discount) but plays no Imperial unit; after passing to the next action phase the discount has
#// expired, so SEC_080 (Imperial, cost 2) costs the full 2 (from 3 readied resources → 1 left).
## GIVEN
CommonSetup: ggk/ggk/{myResources:3;handCardIds:ASH_237,SEC_080}
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080]
## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0
## EXPECT
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:1:CARDID:SEC_080
