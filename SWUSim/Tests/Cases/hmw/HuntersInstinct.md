# CreatureHost_GainsGrit
#// COVERAGE: offer=N/A (STRUCTURAL: a conditional keyword grant; the host pick is the generic attach)
#//           decline=N/A (STRUCTURAL: no "may") · boundary=N/A (STRUCTURAL: a trait test)
#//           negative=NonCreatureHost_NoGrit · control=N/A (the grant reads the host; no owner-scoped zone)
#//           reqboundary=N/A (STRUCTURAL: live read) · duration=RemovedUpgrade_GritGone
#//           dispatch=PlayedFromHand_OnACreature · modes=2P only (no player reference; no friendly/enemy wording)
#//
#// HMW_191 Hunter's Instinct — Upgrade +2/+1, cost 3, [Aggression], Innate.
#// "If attached unit is a Creature, it gains Grit. (It gets +1/+0 for each damage on it.)"
#// HMW_087 Venomous Wyyyshokk is a 3/4 Creature.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:0
WithP1GroundArenaUpgrade: 0:HMW_191

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5

---

# GritCountsDamage
#// 5/5 with 2 damage → 7 power.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_087:1:2
WithP1GroundArenaUpgrade: 0:HMW_191

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7

---

# NonCreatureHost_NoGrit
#// SOR_095 (Rebel/Trooper) with 2 damage: +2 from the upgrade only.

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:2
WithP1GroundArenaUpgrade: 0:HMW_191

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:5

---

# PlayedFromHand_OnACreature

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_191
WithP1GroundArena: HMW_087:1:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:6

---

# RemovedUpgrade_GritGone

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_251
WithP1GroundArena: HMW_087:1:1
WithP1GroundArenaUpgrade: 0:HMW_191

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Grit
P1GROUNDARENAUNIT:0:POWER:3
