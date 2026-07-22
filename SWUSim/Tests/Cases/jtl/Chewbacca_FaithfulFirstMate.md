# AsPilot_HostDefeatImmune
#// JTL_103 Chewbacca (Pilot) — "Attached unit gains: 'This unit can't be defeated ... by enemy card
#// abilities.'" P2's SOR_237 carries the Chewbacca pilot. P1 plays Direct Hit (JTL_078: defeat a
#// non-leader Vehicle) at SOR_237; it fizzles because the host has the Chewbacca-granted defeat immunity.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:JTL_078}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237

---

# BounceImmune
#// JTL_103 Chewbacca — "... or returned to hand by enemy card abilities." P1 plays Waylay (SOR_222:
#// return a non-leader unit to its owner's hand) targeting Chewbacca; it fizzles and Chewbacca stays in
#// play (P2's hand stays empty).

## GIVEN
CommonSetup: yyk/rrk/{myResources:8;handCardIds:SOR_222}
P1OnlyActions: true
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:JTL_103
P2HANDCOUNT:0

---

# DefeatImmune_MassDefeat
#// JTL_103 Chewbacca — "This unit can't be defeated ... by enemy card abilities." P1 plays Nebula
#// Ignition (JTL_080: defeat each unit that isn't upgraded). The enemy SEC_080 is defeated, but Chewbacca
#// survives despite being unupgraded — he's immune to defeat by an enemy card ability.

## GIVEN
CommonSetup: bbw/rrk/{myResources:12;handCardIds:JTL_080}
P1OnlyActions: true
WithP2GroundArena: JTL_103:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:JTL_103

---

# DiesToCombat
#// JTL_103 Chewbacca — the immunity is to ENEMY CARD ABILITIES only; it does NOT prevent a combat defeat.
#// A pre-damaged Chewbacca (5 of 6 HP) attacks SEC_080, takes 3 counter damage, and is defeated by having
#// no remaining HP.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: JTL_103:1:5
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# DamagedByEnemyAbility_AsUnit
#// JTL_103 Chewbacca (5/6) — the immunity is defeat/return only; enemy card abilities can still DAMAGE it.
#// P2's Daring Raid (TWI_170) deals 2 to Chewbacca; it takes 2 and survives.

## GIVEN
CommonSetup: rrk/rrk/{theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: JTL_103:1:0
WithP2Hand: TWI_170

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_103
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# DefeatedByFriendlyAbility_AsUnit
#// JTL_103 Chewbacca — "can't be defeated by ENEMY card abilities" does not restrict the controller's own
#// abilities. P1's Rival's Fall (SHD_079, "Defeat a unit") defeats P1's own Chewbacca normally.

## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:2

---

# SelectableByEnemyButNotDefeated_AsUnit
#// JTL_103 Chewbacca is a legal TARGET for an enemy defeat ability, but the defeat is prevented. P2's
#// Vanquish (TWI_077, "Defeat a non-leader unit") can select Chewbacca (it appears in the target list),
#// yet Chewbacca is not defeated — it stays in the arena and the other unit is untouched.

## GIVEN
CommonSetup: rrk/bbk/{theirResources:6}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: [JTL_103:1:0 SOR_095:1:0]
WithP2Hand: TWI_077

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:JTL_103
P1GROUNDARENAUNIT:1:CARDID:SOR_095

---

# AsPilot_HostBounceImmune
#// JTL_103 Chewbacca (Pilot) — "Attached unit gains: 'This unit can't be … returned to hand by enemy
#// card abilities.'" P2's SOR_102 Home One carries the Chewbacca pilot. P1 plays Waylay (SOR_222:
#// return a non-leader unit to its owner's hand) at Home One; it fizzles because the host has the
#// Chewbacca-granted return immunity. Home One stays in play; P2's hand stays empty.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:SOR_222}
P1OnlyActions: true
WithP2SpaceArena: SOR_102:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_102
P2SPACEARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0

---

# AsPilot_HostDamagedByEnemyAbility
#// JTL_103 Chewbacca (Pilot) — the granted immunity is defeat/return only; enemy card abilities can
#// still DAMAGE the host. P1 plays Open Fire (TWI_174: deal 4 damage to a unit) at P2's SOR_102 Home
#// One (7 HP) carrying the Chewbacca pilot; Home One takes 4 and survives.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:TWI_174}
P1OnlyActions: true
WithP2SpaceArena: SOR_102:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_102
P2SPACEARENAUNIT:0:DAMAGE:4

---

# AsPilot_UpgradeItselfDefeatedByEnemyAbility
#// JTL_103 Chewbacca (Pilot) — the immunity protects the HOST, not the Chewbacca UPGRADE itself. P1
#// plays Confiscate (SHD_262: "Defeat an upgrade") while the Chewbacca pilot is the only upgrade in
#// play → it auto-targets and defeats the pilot upgrade (to its owner P2's discard). Home One survives
#// with no upgrades.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:SHD_262}
P1OnlyActions: true
WithP2SpaceArena: SOR_102:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_102
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1

---

# AsPilot_UpgradeReturnedByEnemyAbility
#// JTL_103 Chewbacca (Pilot) — the host's return immunity does not protect the pilot UPGRADE from being
#// moved off it. P1 plays Bamboozle (SOR_199: "Exhaust a unit and return each upgrade on it to its
#// owner's hand") on P2's Home One → Home One is exhausted and stays, the Chewbacca pilot returns to
#// its owner P2's hand.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8;handCardIds:SOR_199}
P1OnlyActions: true
WithP2SpaceArena: SOR_102:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_102
P2SPACEARENAUNIT:0:UPGRADECOUNT:0
P2SPACEARENAUNIT:0:EXHAUSTED
P2HANDCOUNT:1

---

# AsPilot_HostDefeatedByFriendlyAbility
#// JTL_103 Chewbacca (Pilot) — "can't be defeated by ENEMY card abilities" does not restrict the
#// host controller's OWN abilities. P1 controls SOR_102 Home One carrying its own Chewbacca pilot and
#// plays its own Vanquish (TWI_077: defeat a non-leader unit) at Home One → the host is defeated
#// normally, and both the host and the Chewbacca pilot land in P1's discard (2 cards).

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TWI_077
WithP1SpaceArena: SOR_102:1:0
WithP1SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:3

---

# DefeatedByLethalAbilityDamage_AsUnit
#// JTL_103 Chewbacca (unit, 5/6) — the immunity is to being DEFEATED or RETURNED by an enemy card
#// ability; it does NOT stop enemy ability DAMAGE, so LETHAL ability damage defeats it (defeat by damage
#// is a different game action than defeat by ability). P1 plays SOR_135 Emperor Palpatine, whose When
#// Played deals 6 damage divided among enemy units — all 6 land on Chewbacca (the only enemy unit,
#// auto-assigned), which has 6 HP and is defeated.

## GIVEN
CommonSetup: rrk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_135
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:6

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_135

---

# ReturnedByFriendlyAbilityWhenStolen_AsUnit
#// JTL_103 Chewbacca (unit) — once an ENEMY takes control of it, the controller's own abilities are
#// no longer "enemy" to it, so the return immunity lapses. P1 plays Change of Heart (SOR_224: take
#// control of a non-leader unit) to steal P2's Chewbacca (auto-resolves, the only legal target); P2
#// passes; P1 — now Chewbacca's controller — plays Waylay (SOR_222) at it, and the return succeeds
#// because it is now a FRIENDLY ability. Chewbacca returns to its OWNER P2's hand.

## GIVEN
CommonSetup: yrw/rrk/{myResources:12}
SkipPreGame: true
WithP1Hand: SOR_224
WithP1Hand: SOR_222
WithP2GroundArena: JTL_103:1:0

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:JTL_103

---

# AsPilot_HostReturnedByFriendlyAbilityWhenStolen
#// JTL_103 Chewbacca (Pilot) — same lapse-on-steal, host side. P1 plays Change of Heart to steal P2's
#// SOR_102 Home One (carrying the Chewbacca pilot); P2 passes; P1 — now the host's controller — plays
#// Waylay at it. The return is now friendly, so it succeeds: Home One goes to its OWNER P2's hand and
#// the Chewbacca pilot (which can't ride to hand) is defeated to its owner P2's discard.

## GIVEN
CommonSetup: yrw/rrk/{myResources:12}
SkipPreGame: true
WithP1Hand: SOR_224
WithP1Hand: SOR_222
WithP2SpaceArena: SOR_102:1:0
WithP2SpaceArenaUpgrade: 0:JTL_103

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SOR_102
P2DISCARDCOUNT:1

---

# DefeatedByEnemyAbilityAfterForceLightning_AsUnit
#// JTL_103 Chewbacca (unit) — Force Lightning (SOR_138: "Choose a unit. It loses all abilities for
#// this phase.") strips Chewbacca's own defeat immunity, after which an ordinary enemy defeat lands.
#// P2 plays Force Lightning at Chewbacca (P2 controls no Force unit, so the pay/damage half is skipped);
#// P1 passes; P2 plays Rival's Fall (SHD_079: defeat a unit) at the now-ability-less Chewbacca, which
#// is defeated to its owner P1's discard.

## GIVEN
CommonSetup: bbw/rrk/{theirResources:14}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: JTL_103:1:0
WithP2Hand: SOR_138
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# AsPilot_HostDefeatedByEnemyAbilityAfterForceLightning
#// JTL_103 Chewbacca (Pilot) — Force Lightning strips the GRANTED immunity from the host. P2 plays
#// Force Lightning at P1's SOR_102 Home One (carrying the Chewbacca pilot) → it loses all abilities,
#// including the Chewbacca-granted defeat immunity, for the phase; P1 passes; P2 plays Rival's Fall at
#// Home One, which is now defeatable. Host and pilot both land in owner P1's discard (2 cards).

## GIVEN
CommonSetup: bbw/rrk/{theirResources:14}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_102:1:0
WithP1SpaceArenaUpgrade: 0:JTL_103
WithP2Hand: SOR_138
WithP2Hand: SHD_079

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2

---

# AsPilot_HostSelectableByEnemyButNotDefeated
#// JTL_103 Chewbacca (Pilot) — the host is a legal SELECTION for an enemy defeat ability, but the
#// defeat itself is prevented. P2 plays Avenger (SOR_040: "An opponent chooses a non-leader unit they
#// control. Defeat that unit."). The opponent P1 chooses SOR_102 Home One (carrying the Chewbacca
#// pilot) — a legal choice — yet the granted immunity prevents its defeat: Home One survives, and P1's
#// other unit (SOR_095) is untouched.

## GIVEN
CommonSetup: bbw/rrk/{theirResources:12}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_102:1:0
WithP1SpaceArenaUpgrade: 0:JTL_103
WithP1GroundArena: SOR_095:1:0
WithP2Hand: SOR_040

## WHEN
- P2>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_102
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
