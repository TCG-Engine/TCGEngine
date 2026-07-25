# RaidWhenLeaderDefeated
#// ASH_093 Captain Pellaeon (Ground, 2/4) — While a leader unit has been defeated this phase, he gains
#// Raid 3. P1's SOR_046 attacks and defeats P2's deployed Iden Versio (4/4, pre-damaged to 3 HP); then
#// ASH_093 attacks the enemy base with Raid 3 → 2 + 3 = 5 damage.
## GIVEN
CommonSetup: brw/bbk/{
  theirLeader:SOR_002:1:1:0:1;
  myLeader:SOR_013;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1GroundArena: ASH_093:1:0
WithP1GroundArena: SOR_046:1:0
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5

---

# NoLeaderDefeated_NoRaid
#// ASH_093 Captain Pellaeon — the Raid 3 only applies while a leader unit has been defeated this phase. With
#// no leader defeated, Pellaeon (2 power) attacks the enemy base for just 2.
## GIVEN
CommonSetup: brw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_093:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:2

---

# NonLeaderDefeated_NoRaid
#// ASH_093 Captain Pellaeon — the Raid 3 requires a LEADER unit to have been defeated this phase; defeating
#// an ordinary unit does not qualify. P1's SOR_046 defeats P2's non-leader SEC_080, then Pellaeon attacks the
#// enemy base for just 2 (no Raid).
## GIVEN
CommonSetup: brw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: ASH_093:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2

---

# OwnLeaderDefeated_GrantsRaid
#// ASH_093 Captain Pellaeon — the Raid 3 counts ANY leader unit defeated this phase, including P1's OWN
#// leader unit. P1's deployed leader (SOR_013, pre-damaged to 3 remaining HP) is defeated by P2's Consular
#// Security Force (SOR_046, 3 power); Pellaeon then attacks the enemy base with Raid 3 → 2 + 3 = 5.
## GIVEN
CommonSetup: brw/bbk/{myLeader:SOR_013:1:1:0:3;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithP1GroundArena: ASH_093:1:0
WithP2GroundArena: SOR_046:1:0
WithActivePlayer: 2
## WHEN
- P2>AttackGroundArena:0:1
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5

---

# RaidWhenDarksaberLeaderUnitDefeated
#// ASH_093 Pellaeon — a unit made a LEADER UNIT by The Darksaber (ASH_135) counts for "a leader unit has been
#// defeated this phase" even though its printed type is Unit. P1's SOR_046 defeats the enemy R2-D2 (SOR_236,
#// pre-damaged) wearing the Darksaber, so Pellaeon then attacks the base with Raid 3 → 2 + 3 = 5.
## GIVEN
CommonSetup: brw/bbk/{myBase:SOR_021;theirBase:SOR_021}
P1OnlyActions: true
WithP1GroundArena: ASH_093:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_236:1:3
WithP2GroundArenaUpgrade: 0:ASH_135
## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:5
