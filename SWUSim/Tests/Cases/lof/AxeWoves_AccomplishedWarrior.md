# PlusPerUpgrade
#// LOF_062 Axe Woves (2/2) — Shielded + "This unit gets +1/+1 for each upgrade on him." With two
#// Experience tokens (each +1/+1), plus +1/+1 per upgrade, he is 2 + 2(Exp) + 2(per-upgrade) = 6/6.

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: LOF_062:1:0
WithP1GroundArenaUpgrade: 0:SOR_T01
WithP1GroundArenaUpgrade: 0:SOR_T01

## EXPECT
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6

---

# BaseStatsNoUpgrades
#// LOF_062 Axe Woves — with no upgrades on him, the "+1/+1 for each upgrade" adds nothing, so he is a plain
#// 2/2. Ref: "should be 2/2 without any upgrades".

## GIVEN
CommonSetup: bbw/rrk
WithP1GroundArena: LOF_062:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:2

---

# EnemyBountyUpgradeCounts
#// LOF_062 Axe Woves — "for each upgrade on him" counts ENEMY-controlled upgrades too. He starts 2/2; the
#// opponent plays the bounty Top Target (SHD_071) onto him, and that single upgrade gives him +1/+1 → 3/3.
#// Ref: opponent plays a bounty on Axe Woves giving him +1/+1 from the ability.

## GIVEN
CommonSetup: bbw/rrk/{theirBase:SOR_021}
WithActivePlayer: 2
WithP1Resources: 3
WithP2Resources: 8
WithP2Hand: SHD_071
WithP1GroundArena: LOF_062:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3

---

# ShieldedOnPlayCountsAsUpgrade
#// LOF_062 Axe Woves — he has Shielded, so on entering play he gains a Shield token, which is an upgrade and
#// counts toward "+1/+1 for each upgrade on him." Played fresh from hand he is therefore 3/3 (2/2 + the Shield
#// upgrade). Ref: after playing Axe Woves he is 3/3 from the ability (the Shield token counts).

## GIVEN
CommonSetup: bbw/rrk/{myResources:5;handCardIds:LOF_062}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
