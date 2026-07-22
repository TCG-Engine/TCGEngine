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
