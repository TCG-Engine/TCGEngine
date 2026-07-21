# Passive_PlusOnePerDamagedUnit
#// JTL_170 War Juggernaut — passive: This unit gets +1/+0 for each damaged unit. With two damaged units
#// in play (SOR_095 and SOR_046), the undamaged Juggernaut (printed 3 power) is at 3+2=5.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_170:1:0
WithP1GroundArena: SOR_095:1:1
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_170
P1GROUNDARENAUNIT:0:POWER:5

---

# WhenPlayed_Deal1ToAnyNumber
#// JTL_170 War Juggernaut — When Played: Deal 1 damage to each of any number of units. P1 picks both
#// enemy units (each takes 1). The two newly-damaged units also raise the Juggernaut's own power
#// (3 + 2 damaged = 5), exercising the passive together with the AOE.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_170
WithP1Resources: 6
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:0:CARDID:JTL_170
P1GROUNDARENAUNIT:0:POWER:5

---

# Passive_NoDamagedUnits_BasePower
#// JTL_170 War Juggernaut — the +1/+0-per-damaged-unit passive contributes 0 when nothing is damaged. With
#// only undamaged units in play (itself and an undamaged friendly), the Juggernaut sits at its printed 3
#// power.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_170:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_170
P1GROUNDARENAUNIT:0:POWER:3

---

# WhenPlayed_ChooseNothing
#// JTL_170 War Juggernaut — the When Played "deal 1 to each of any number of units" allows choosing NO
#// targets. With a pre-damaged enemy present (Juggernaut at 3+1=4 power), P1 declines to deal any damage:
#// no new damage is applied and the enemy's damage is unchanged.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_170
WithP1Resources: 6
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:CARDID:JTL_170
P1GROUNDARENAUNIT:0:POWER:4

---

# WhenPlayed_TargetsSelfAndFriendly
#// JTL_170 War Juggernaut — the AOE may target ANY units, including itself and friendly units. P1 pings its
#// own newly-played Juggernaut, a friendly unit, and an enemy unit (1 each). All three become damaged, so
#// the passive counts 3 → Juggernaut power 3+3 = 6, and it carries its own 1 damage.

## GIVEN
CommonSetup: grw/bbk/{
  myLeader:JTL_012;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_170
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1&theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:JTL_170
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:1:POWER:6
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1
