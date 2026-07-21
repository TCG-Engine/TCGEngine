# GrantsGrit
#// JTL_034 Interceptor Ace (pilot) — Attached unit gains Grit. SOR_237 with the pilot attached and 2
#// damage gets +2/+0 from the granted Grit.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:2
WithP1SpaceArenaUpgrade: 0:JTL_034

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:POWER:6

---

# AsUnit_Grit
#// JTL_034 Interceptor Ace — as a UNIT it has Grit itself. Seated with 2 damage, JTL_034 (2/3) gets +2/+0
#// from Grit → power 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_034:1:2

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_034
P1GROUNDARENAUNIT:0:POWER:4

---

# GrantedGrit_NoDamage_NoBonus
#// JTL_034 Interceptor Ace (pilot) — the granted Grit is a value keyword: with NO damage on the host it adds
#// nothing. SOR_237 (2) + the pilot's flat +2 power = 4, and Grit's +0 (no damage) leaves it at 4.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_034

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:POWER:4

---

# GrantedGrit_HostAlreadyHasGrit_NoDouble
#// JTL_034 Interceptor Ace (pilot) — the granted Grit does NOT stack with a host that already has innate
#// Grit. Host TWI_036 (Devastating Gunship, 3/5, Grit) with 2 damage: base 3 + pilot +2/+3 + Grit for 2
#// damage (+2, counted ONCE across both Grit sources) → power 7, hp 8 (NOT 3+2+2+2=9).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: TWI_036:1:2
WithP1SpaceArenaUpgrade: 0:JTL_034

## WHEN

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_036
P1SPACEARENAUNIT:0:POWER:7
P1SPACEARENAUNIT:0:HP:8
P1SPACEARENAUNIT:0:HASKEYWORD:Grit
