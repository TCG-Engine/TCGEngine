# HeroismHost_GetsAShield
#// COVERAGE: offer=N/A (STRUCTURAL: nothing is chosen — "it" is the attached unit) · decline=N/A
#//           (STRUCTURAL: mandatory) · boundary=N/A (STRUCTURAL: an aspect test, not a number)
#//           gate negatives=VillainyHost_NoShield and VigilanceOnlyHost_NoShield
#//           control=N/A (no owner-scoped zone or "your" wording) · reqboundary=N/A (no decision between
#//           the attach and the Shield — both resolve inside the play)
#//           dispatch=EnemyHeroismHost_ShieldGoesToIt · target=TwoHosts_ShieldGoesOnlyToTheAttachedOne
#//           modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_264 Heroic Bravery — Upgrade +0/+2, cost 2, [Heroism], Innate.
#// "When Played: If attached unit is a Heroism unit, give a Shield token to it."
#// SOR_095 Battlefield Marine is [Command][Heroism]; it is the only unit, so the upgrade auto-attaches.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_264
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:HP:5
P1NODECISION

---

# VillainyHost_NoShield

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_264
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# VigilanceOnlyHost_NoShield
#// SOR_063 is [Vigilance] only — "Heroism" is not "not Villainy".

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_264
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# EnemyHeroismHost_ShieldGoesToIt
#// No friendly unit: it attaches to P2's lone SOR_095, a Heroism unit — the Shield goes to that host.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_264
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:HP:5

---

# TwoHosts_ShieldGoesOnlyToTheAttachedOne
#// Two Heroism units: attach to the second — the first must not get the Shield.

## GIVEN
CommonSetup: yyw/yyw/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_264
WithP1GroundArena: [SOR_095:1:0 SOR_046:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:HP:9
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
