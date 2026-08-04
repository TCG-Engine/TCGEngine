# Alone_RaidOneOnly
#// IC27_071 Avar Kriss (For Light and Life) — 2 cost, 0/5, Command+Heroism, Ground, Force/Jedi/Republic.
#// Text: "Raid 1 (reminder) / This unit gains Raid 1 for each other friendly unit."
#// Her printed power is ZERO, so base damage on an attack IS her Raid total — the cleanest
#// behavioral readout of the computed value.
#// Alone: printed Raid 1 + 0 others = 1.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1

---

# OneOtherFriendly_RaidTwo
#// THE QUANTITY DISCRIMINATOR. With exactly one other friendly unit the three plausible readings
#// separate: 1 = "flat Raid 1, rider ignored"; 2 = CORRECT (1 printed + 1 per OTHER); 3 = counted
#// itself as well. Nothing but this middle case tells them apart.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# TwoOtherFriendlies_RaidThree
#// Scales per unit, not a one-shot "+1 while you control another unit".

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3

---

# EnemyUnitsDoNotCount
#// The "friendly" gate is load-bearing: three ENEMY units must not raise Raid at all.
#// Attacking :BASE explicitly because enemy units are present.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1

---

# FriendlySpaceUnitCounts
#// No arena qualifier — a friendly SPACE unit counts toward a GROUND unit's Raid.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# TokenUnitCounts
#// Value-CLASS variant: a Token Unit is a friendly unit and counts.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0
WithP1GroundArena: TWI_T01:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# DeployedLeaderUnitCounts
#// Value-CLASS variant: a deployed leader unit counts too (no non-leader qualifier).
#// The leader unit is appended after Avar, so she stays at ground index 0.

## GIVEN
CommonSetup: ggw/ggw/{myLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P2BASEDMG:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT

---

# FriendlyDies_RaidRecomputesLive
#// PERSISTENCE / live-recompute: the count must be read at combat time, not cached at entry.
#// LAW_180 (3/1) trades with SEC_080 (3/3) — both die — leaving Avar alone, so her second-action
#// attack deals 1, not the 2 it would have dealt before the trade.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0
WithP1GroundArena: LAW_180:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_071
P2GROUNDARENACOUNT:0
P2BASEDMG:1

---

# NotAttacking_PrintedPowerStaysZero
#// Raid is a while-ATTACKING bonus. With two other friendlies her Raid total is 3, but her
#// printed power must remain 0 — proving the rider is not leaking into ObjectCurrentPower.

## GIVEN
CommonSetup: ggw/ggw/{}
WithP1GroundArena: IC27_071:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_071
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid

---

# RaidAppliesAttackingAUnitToo
#// Every other section reads the Raid total off BASE damage, which is one branch of
#// SWUCombatDamage. Raid is "+1/+0 while attacking" generally, so prove the computed value also
#// reaches the UNIT-combat branch: Raid 2 (1 printed + 1 other friendly) on a 0-power body deals 2
#// to a 3/3, which survives and counters for 3 into her 5 HP.

## GIVEN
CommonSetup: ggw/ggw/{}
P1OnlyActions: true
WithP1GroundArena: IC27_071:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:CARDID:IC27_071
P1GROUNDARENAUNIT:0:DAMAGE:3
