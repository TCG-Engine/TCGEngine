# Passive_DamagedUnitGetsBoost
#// Krennic leader passive: friendly damaged unit gets +1/+0.
#// SOR_095 has base power 3. With 1 damage, Krennic's passive gives it +1 -> power 4.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:1

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4

---

# Passive_UndamagedUnitNoBoost
#// Krennic passive only triggers on damaged units.
#// SOR_095 with 0 damage gets no boost -> power stays at 3.

## GIVEN
CommonSetup: gbk/grw/{
  myLeader:SOR_001
}
SkipPreGame: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3

---

# SabineWrenLeaderUnitSide
## GIVEN
CommonSetup: grw/grw/{myResources:4}
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2GroundArena: SOR_095

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:1
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1LEADER:EPICUSED
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:2
