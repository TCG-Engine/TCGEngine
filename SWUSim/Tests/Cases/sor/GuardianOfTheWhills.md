# FirstUpgrade_CostReduced
#// COVERAGE: offer=N/A - STRUCTURAL: a play-cost modifier selects nothing.
#//           decline=N/A - STRUCTURAL: nothing optional.
#//           boundary=FirstUpgrade_CostReduced / SecondUpgrade_FullCost (the "first each round" charge
#//           IS the threshold, written as a pair) + ZeroCostUpgrade_ChargeNotWasted
#//           control=N/A - STRUCTURAL: the discount is scoped to upgrades played ON THIS UNIT by its
#//           controller; no owner-scoped zone is named.
#//           reqboundary=N/A - ⚠ SITUATIONAL: the once-per-round charge is phase/round state read on a
#//           later action. SecondUpgrade_FullCost crosses two ACTIONS and so exercises the persistence
#//           in practice, but no explicit boundary section exists. Open cell.
#//           modes=2P only - "you play on this unit" is self-scoped in every format.
#// SOR_061 Guardian of the Whills (Unit 2/2, Vigilance) — "The first upgrade you play on this unit each
#// round costs 1 less." The Guardian is the only friendly unit, so SOR_069 Resilient (+0/+3, Vigilance,
#// cost 1) auto-attaches to it and the discount makes it cost 0: 3 ready resources → 3 left. The host
#// becomes 2/5 with one upgrade.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HP:5
P1RESAVAILABLE:3

---

# SecondUpgrade_FullCost
#// SOR_061 Guardian of the Whills — only the FIRST upgrade each round is discounted. Two SOR_069
#// (cost 1) on the same Guardian: the first costs 0 (charge spent), the second costs the full 1.
#// 3 ready resources → 0 + 1 = 2 left. (If the charge weren't consumed, both would be free → 3 left.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:2

---

# TwoGuardians_TwoDiscounts
#// SOR_061 Guardian of the Whills — each Guardian has its OWN per-round charge, so two Guardians grant
#// two separate discounts. Two SOR_069 (cost 1), each attached to a different Guardian, both cost 0:
#// 4 ready resources → 4 left. (One discount only → 3 left; no discount → 2 left.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SOR_069
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:4

---

# UpgradeElsewhere_NoNetDiscount
#// SOR_061 Guardian of the Whills — the discount applies only to upgrades that actually land ON the
#// Guardian. With a Guardian (idx 0) and a non-Guardian unit (SOR_095, idx 1) both in play, P1 plays
#// SOR_069 (cost 1) onto SOR_095. The affordability gate showed -1 (a Guardian is in play), but ATTACH
#// reconciles: the upgrade went elsewhere, so the 1 is clawed back → net full cost (3 → 2). The
#// Guardian's charge stays UNUSED. (If the reconcile leaked the discount, RESAVAILABLE would be 3.)

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2

---

# ZeroCostUpgrade_ChargeNotWasted
#// SOR_061 Guardian of the Whills — attaching a 0-cost upgrade (SHD_068 Public Enemy, cost 0)
#// must NOT consume the Guardian's per-round charge (the −1 discount would do nothing on a 0-cost
#// card). After the 0-cost upgrade attaches, the charge is still available for the next upgrade.
#// SOR_069 Resilient (cost 1) then attaches and gets the −1 → costs 0. Total spent = 0 + 0 = 0.
#// 3 ready resources → still 3 left. If the charge were wasted on SHD_068, SOR_069 would cost 1
#// → 2 resources left.

## GIVEN
CommonSetup: bbk/bbk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_061:1:0
WithP1Hand: SHD_068
WithP1Hand: SOR_069

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1RESAVAILABLE:3

---

# GuardianUpgradeDiscountAppliesToSmuggledUpgrade
#// SOR_061 Guardian of the Whills — "the first upgrade you play on this unit each round costs 1 less." This
#// host-conditional discount must also reduce an upgrade played onto the Guardian via SMUGGLE (Phase 3: the
#// Smuggle path applies the used-flag bucket, and SMUGGLE_ATTACH consumes host-conditional flags against the
#// REAL chosen host). P1 controls the Guardian and smuggles SHD_174 Hotshot DL-44 Blaster (Smuggle [3
#// Cunning]; yyk base covers Cunning → bracket 3, minus Guardian's -1 = 2). With exactly 2 ready resources
#// it attaches to the Guardian (only host → auto-resolves). Paired with the non-Guardian host below (needs 3).

## GIVEN
CommonSetup: yyk/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: SOR_061:1:0
WithP1Resources: 1:SHD_174:1,1:SOR_251:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NonGuardianHost_SmuggledUpgradeCostsFull
#// Control: same as above but the only host is a vanilla SOR_046 (not a Guardian), so no Guardian discount
#// exists — SHD_174 costs the full bracket 3. With only 2 ready resources it CANNOT be played and does not
#// attach (upgrade count 0). This proves the 2-resource attach above only succeeds because of the Guardian's
#// -1, and that the discount is evaluated host-correctly (a best-case peek is not mis-applied here).

## GIVEN
CommonSetup: yyk/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP1Resources: 1:SHD_174:1,1:SOR_251:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# SmuggledUpgradeConsumesGuardianCharge
#// Phase 3 consume guard: smuggling an upgrade onto the Guardian must SPEND its once-per-round "first
#// upgrade" charge (via SMUGGLE_ATTACH → _SWUConsumeUpgradeUsedFlags against the real host), so a LATER
#// upgrade on the Guardian pays full. P1 smuggles SHD_174 onto the Guardian for 2 (bracket 3 - 1), leaving
#// 0 ready. Then P1 tries to play SOR_214 Smuggling Compartment (cost 1 Cunning) from hand onto the
#// Guardian: the charge is spent, so it costs the full 1 > 0 ready → it CANNOT attach (Guardian upgrade
#// count stays 1). Were the charge NOT consumed, SOR_214 would be discounted to 0 and attach free (count 2).

## GIVEN
CommonSetup: yyk/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: SOR_061:1:0
WithP1Resources: 1:SHD_174:1,1:SOR_251:1
WithP1Hand: SOR_214

## WHEN
- P1>SmuggleResource:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
