# EnemyDamagedBuff
#// JTL_141 IG-88 — While an enemy unit is damaged, this unit gets +3/+0. With P2's SOR_046 damaged,
#// IG-88 (base power 4) has power 7.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_141:1:0
WithP2GroundArena: SOR_046:1:3

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7

---

# NoEnemyDamaged_NoBuff
#// JTL_141 IG-88 — the +3/+0 applies only WHILE an enemy unit is damaged. With P2's SOR_046 present but
#// UNDAMAGED, IG-88 stays at its printed 4 power / 5 HP.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_141:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:5

---

# SpaceEnemyDamaged_GroundBuffed
#// JTL_141 IG-88 — "an enemy unit is damaged" is ARENA-AGNOSTIC: a damaged enemy in the SPACE arena buffs
#// the ground IG-88 just the same. IG-88 (ground) gets +3/+0 → power 7; the buff is +3/+0 so HP stays 5.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_141:1:0
WithP2SpaceArena: SOR_052:1:2

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:5

---

# Piloted_HostGainsBuffWhileEnemyDamaged
#// JTL_141 IG-88 — Piloting grant: "Attached unit gains: 'While an enemy unit is damaged, this unit gets
#// +3/+0.'" IG-88 attached to SOR_249 (3/5 Walker; the harness seats it as a plain upgrade → +0/+3 pilot
#// stats, no separate pilot power). With P2's SOR_046 damaged the host gets the granted +3/+0 → power
#// 3+0+3 = 6; HP is 5+3 = 8 in both states (the granted buff is +3/+0, so it doesn't move HP).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_249:1:0
WithP1GroundArenaUpgrade: 0:JTL_141
WithP2GroundArena: SOR_046:1:3

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:8

---

# Piloted_NoBuffWhenNoEnemyDamaged
#// JTL_141 IG-88 — the GRANTED buff, like the unit-side one, is conditional. SOR_249 carrying IG-88 with
#// NO enemy damaged stays at 3 power (3+0, no +3); HP is still 5+3 = 8 (the pilot's flat HP contribution).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_249:1:0
WithP1GroundArenaUpgrade: 0:JTL_141
WithP2GroundArena: SOR_046:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_249
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:8

---

# BuffDropsWhenDamagedEnemyDefeated
#// JTL_141 IG-88 — the buff is recomputed live: it drops the moment no enemy is damaged. IG-88 (buffed to
#// power 7 by the damaged SOR_046, 3 dmg → 4 HP remaining) attacks and defeats it; with no enemy left
#// damaged, IG-88 falls back to its printed 4 power. (IG-88 takes 3 counter damage — that's SELF damage,
#// which never feeds its own "enemy damaged" buff.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithActivePlayer: 1
WithP1GroundArena: JTL_141:1:0
WithP2GroundArena: SOR_046:1:3

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:JTL_141
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:DAMAGE:3
