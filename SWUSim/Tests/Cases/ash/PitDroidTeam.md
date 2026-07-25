# FirstUpgradeCheaper
#// ASH_075 Pit Droid Team (Ground, 3/3) — The first upgrade you play on another friendly unit each phase
#// costs 1 resource less. With Pit Droid in play, P1 plays SOR_120 (cost 2, Command) onto SOR_095 (another
#// friendly unit) for 1, leaving 1 of 2 resources.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_120}
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:1

---

# SecondUpgrade_NoDiscount
#// ASH_075 Pit Droid Team — only the FIRST upgrade each phase is discounted. P1 plays two SOR_120s onto
#// SOR_095: the first costs 1 (−1), the second the full 2. From 5 resources, 5−1−2 = 2 left.
## GIVEN
CommonSetup: ggk/ggk/{myResources:5;handCardIds:SOR_120,SOR_120}
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1RESAVAILABLE:2

---

# SecondUpgrade_SeparateUnit_NoDiscount
#// ASH_075 Pit Droid Team — the discount is once per phase across ALL friendly units, not per unit. First
#// upgrade on SOR_095 costs 1 (−1); the second, on a different friendly SEC_135, costs the full 2.
#// From 5 resources: 5 − 1 − 2 = 2 left.
## GIVEN
CommonSetup: gbw/gbw/{myResources:5;handCardIds:SOR_120,SOR_120}
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SEC_135:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-2
## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P1RESAVAILABLE:2

---

# OpponentUpgradeOwnUnit_DoesntConsume
#// ASH_075 Pit Droid Team — an upgrade the OPPONENT plays on their own unit does not consume our once-per-phase
#// discount. After P2 plays SOR_120 on their SEC_080, our first upgrade on SOR_095 is still discounted to 1
#// (from 2 resources → 1 left).
## GIVEN
CommonSetup: gbw/ggk/{myResources:2;handCardIds:SOR_120;theirResources:5;theirhandCardIds:SOR_120}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>Pass
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:1


---

# UpgradeOnSelf_NoDiscount
#// ASH_075 — the discount is for an upgrade on ANOTHER friendly unit; an upgrade on Pit Droid ITSELF pays
#// full price. Playing SOR_120 (cost 2) onto ASH_075 costs 2 (0 of 2 left), not 1.
## GIVEN
CommonSetup: ggk/ggk/{myResources:2;handCardIds:SOR_120}
WithP1GroundArena: ASH_075:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# UpgradeBeforePitDroid_SecondPaysFull
#// ASH_075 — the "first upgrade on another friendly this phase" slot is tracked regardless of when Pit Droid
#// entered. Play SOR_120 on SOR_095 (no Pit Droid → full 2), then play Pit Droid (3), then a second SOR_120 on
#// SOR_095: the first slot is already spent, so it pays full 2 (not discounted). 2 + 3 + 2 = 7 spent.
## GIVEN
CommonSetup: ggk/ggk/{myResources:7;handCardIds:SOR_120,ASH_075,SOR_120}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1RESAVAILABLE:0

---

# UpgradeOnEnemyUnit_NoDiscount_DoesntConsume
#// ASH_075 Pit Droid Team — an upgrade WE play on an ENEMY unit is not "on another friendly unit", so it is
#// not discounted and does not consume the once-per-phase slot. P1 plays Entrenched (SOR_072, cost 2) on the
#// enemy Wampa for the full 2, then the first friendly upgrade SOR_120 (cost 2) on SOR_095 is discounted to 1.
#// From 5 resources: 5 − 2 − 1 = 2 left.
## GIVEN
CommonSetup: gbw/ggk/{myResources:5;handCardIds:SOR_072,SOR_120}
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_164:1:0 SEC_080:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1RESAVAILABLE:2

---

# OpponentUpgradeOurUnit_DoesntConsume
#// ASH_075 Pit Droid Team — an upgrade the OPPONENT plays on OUR unit likewise does not consume our
#// once-per-phase discount. P2 attaches Condemn (SEC_038) onto our SOR_095; afterward our first upgrade on
#// a different friendly unit (SOR_046) is still discounted to 1 (from 2 resources → 1 left).
## GIVEN
CommonSetup: gbw/ggk/{myResources:2;handCardIds:SOR_120;theirResources:5;theirhandCardIds:SEC_038}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1GroundArena: ASH_075:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
## WHEN
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-2
## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:2:CARDID:SOR_046
P1GROUNDARENAUNIT:2:UPGRADECOUNT:1
P1RESAVAILABLE:1
