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
