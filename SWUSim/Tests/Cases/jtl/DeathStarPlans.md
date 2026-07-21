# FirstUnitDiscount
#// JTL_260 Death Star Plans — "Attached unit gains: 'The first unit you play each round costs 2 resources
#// less.'" P1 controls SOR_046 bearing Death Star Plans, then plays JTL_099 (cost 3) which costs 1.
#// Resource check: 10 − 1 = 9 ready left (would be 7 without the discount).

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:JTL_099}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_260

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:9

---

# StealOnAttacked
#// JTL_260 Death Star Plans — "When attached unit is attacked: The attacking player takes control of this
#// upgrade and attaches it to a unit they control." P1's SOR_046 attacks the enemy SEC_080 (which carries
#// Death Star Plans); on attack P1 steals the upgrade onto SOR_046 (its only unit), then combat kills
#// SEC_080 (SOR_046 is a 3/7 and survives the counter).

## GIVEN
CommonSetup: rrk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:JTL_260

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_260
P2GROUNDARENACOUNT:0

---

# SecondUnitNotDiscounted
#// JTL_260 Death Star Plans — the discount is once per phase. P1 controls SOR_046 bearing Death Star
#// Plans and plays TWO units in the same phase. The FIRST (JTL_099, cost 3) is discounted by 2 → costs 1
#// (10 − 1 = 9). The SECOND (SOR_095, cost 2) gets NO discount → costs 2 (9 − 2 = 7). Final ready
#// resources = 7 (would be 9 if the discount wrongly repeated, or 5 with no discount at all).

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:JTL_099,SOR_095}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_260

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:7

---

# NoDiscountForEnemyFirstUnit
#// JTL_260 Death Star Plans — the discount only helps the UPGRADE-CONTROLLER'S units, not the enemy's.
#// P1 controls SOR_046 bearing Death Star Plans. P2 plays their first unit of the phase (SOR_164 Wampa,
#// cost 4). Because P1 (not P2) controls the upgrade, P2 gets NO discount → pays the full 4 (10 − 4 = 6).
#// A wrongly-applied discount would leave 8.

## GIVEN
CommonSetup: ggw/rrk/{theirResources:10}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_260
WithP2Hand: SOR_164

## WHEN
- P2>PlayHand:0

## EXPECT
P2RESAVAILABLE:6

---

# DiscountTransfersOnChangeOfHeart
#// JTL_260 Death Star Plans — the discount belongs to whoever CONTROLS the upgrade. P1 plays their first
#// unit of the phase (JTL_099, cost 3, discounted by 2 → costs 1). P2 then plays Change of Heart
#// (SOR_224, cost 6 Cunning) to take control of the Death Star Plans-bearer SOR_046. Now P2 controls the
#// upgrade and plays their own first unit (SOR_164 Wampa, cost 4) — it is discounted by 2 → costs 2.
#// P2: 10 − 6 (Change of Heart) − 2 (discounted Wampa) = 2 ready (would be 0 if P2 got no discount).
#// Aspects: P1 ggw covers JTL_099 (Command/Heroism); P2 ryk covers Change of Heart (Cunning) + Wampa (Aggression).

## GIVEN
CommonSetup: ggw/ryk/{myResources:10;theirResources:10;handCardIds:JTL_099}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_260
WithP2Hand: [SOR_224 SOR_164]

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2RESAVAILABLE:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_260

---

# FreshDiscountAfterAttackSteal
#// JTL_260 Death Star Plans — after the on-attacked steal moves the upgrade to a new controller, that new
#// controller gets a fresh first-unit discount. P1 plays their first unit (JTL_099, cost 3, discounted → 1).
#// P2's TWI_057 Warrior Drone attacks the Death Star Plans-bearer SOR_046: P2 steals the upgrade and
#// attaches it to their own SOR_159 (a P2 unit). Now P2 controls the upgrade and plays their first unit
#// (SOR_164 Wampa, cost 4) — discounted by 2 → costs 2 (10 − 2 = 8; would be 6 with no discount).
#// Aspects: P1 ggw covers JTL_099; P2 ryk covers Wampa (Aggression). Seated units bypass cost.

## GIVEN
CommonSetup: ggw/ryk/{myResources:10;theirResources:10;handCardIds:JTL_099}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_260
WithP2GroundArena: [TWI_057:1:0 SOR_159:1:0]
WithP2Hand: SOR_164

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:myGroundArena-1
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2RESAVAILABLE:8
P2GROUNDARENAUNIT:1:CARDID:SOR_159
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:JTL_260

---

# PerPlayerTrackingSurvivesTwoControlSwitches
#// JTL_260 Death Star Plans — a player's "already used my first-unit discount this phase" state persists
#// even as the upgrade leaves and returns to them. P1 plays their first unit (SOR_159 Partisan Insurgent,
#// cost 2, discounted by 2 → costs 0, resources stay 10). P2 then steals the upgrade (TWI_057 attacks
#// SOR_046) onto their SOR_164 Wampa. P1 steals it BACK (SOR_157 Cantina Braggart attacks Wampa) onto
#// SOR_046. Now P1 controls Death Star Plans again but has ALREADY used the discount this phase, so
#// playing SHD_055 Moisture Farmer (cost 1) gets NO discount → pays 1 (10 → 9; would be 10 if wrongly
#// re-discounted). Aspects: P1 rbw covers Partisan (Aggression) + Moisture Farmer (Vigilance).

## GIVEN
CommonSetup: rbw/rrk/{myResources:10}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: [SOR_046:1:0 SOR_157:1:0]
WithP1GroundArenaUpgrade: 0:JTL_260
WithP1Hand: [SOR_159 SHD_055]
WithP2GroundArena: [TWI_057:1:0 SOR_164:1:0]

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:1
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1RESAVAILABLE:9
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_260

---

# NoDiscountAfterGainingIfAlreadyPlayedUnit
#// JTL_260 Death Star Plans — gaining control mid-phase does NOT grant a discount if you already played a
#// unit this phase. The "first unit each phase" is counted across ALL of the player's unit plays, not just
#// those made while controlling the upgrade. P2 first plays SOR_157 Cantina Braggart (cost 1) while P1
#// controls the upgrade — no discount, pays 1 (10 → 9). P2 then steals the upgrade by attacking SOR_046
#// with TWI_057 Warrior Drone, attaching it to their own SOR_159. P2 now controls Death Star Plans, but
#// has ALREADY played a unit this phase, so playing SOR_164 Wampa (cost 4) gets NO discount → pays the
#// full 4 (9 → 5; would be 7 if wrongly discounted). Aspects: P2 rrk covers Cantina Braggart + Wampa
#// (Aggression). Seated units bypass cost.

## GIVEN
CommonSetup: ggw/rrk/{theirResources:10}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:JTL_260
WithP2GroundArena: [TWI_057:1:0 SOR_159:1:0]
WithP2Hand: [SOR_157 SOR_164]

## WHEN
- P2>PlayHand:0
- P2>AttackGroundArena:0:0
- P2>AnswerDecision:myGroundArena-1
- P2>PlayHand:0

## EXPECT
P2RESAVAILABLE:5
P2GROUNDARENAUNIT:1:CARDID:SOR_159
P2GROUNDARENAUNIT:1:UPGRADE:0:CARDID:JTL_260
