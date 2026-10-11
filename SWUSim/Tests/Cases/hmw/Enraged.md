# AttachedUnitGainsRaidTwo
#// COVERAGE: offer=N/A (STRUCTURAL: the upgrade's only text is a keyword grant — its host pick is the generic
#//           upgrade attach) · decline=N/A (STRUCTURAL: no "may")
#//           boundary=N/A (STRUCTURAL: a fixed value) · quantity=TwoCopiesStack + StacksWithPrintedRaid
#//           control=PlayedOnAnEnemyUnit_TheEnemyGainsRaid (a granted ability belongs to the host's controller)
#//           reqboundary=N/A (STRUCTURAL: live read) · duration=RemovedUpgrade_RaidGone
#//           negative=Defending_NoRaid · modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_190 Enraged — Upgrade +1/+1, cost 2, [Aggression], Innate.
#// "Attached unit gains Raid 2. (It gets +2/+0 while attacking.)"

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_190

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2
P1GROUNDARENAUNIT:0:POWER:4

---

# RaidAddsDamageWhileAttacking
#// SOR_095 3/3 +1/+1 = 4, Raid 2 → 6 to the base.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_190

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:6

---

# TwoCopiesStack

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: [0:HMW_190 0:HMW_190]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:4
P2BASEDMG:9

---

# StacksWithPrintedRaid
#// HMW_131 Soaring Can-Cell prints Raid 1: 1 + 2 = Raid 3.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_131:1:0
WithP1GroundArenaUpgrade: 0:HMW_190

## EXPECT
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:3

---

# Defending_NoRaid
#// P2's SOR_046 (3/7) attacks the 4/4 SOR_095 — Raid is attack-only, so the counter is 4.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_190
WithP2GroundArena: SOR_046:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# PlayedOnAnEnemyUnit_TheEnemyGainsRaid

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_190
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:2

---

# RemovedUpgrade_RaidGone
#// SOR_251 Confiscate defeats the Enraged — the grant goes with it.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:HMW_190

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:KEYWORDVALUE:Raid:0
