# ShieldEnemyDiscountsNextEvent
#// TS26_35 Ahsoka's Lightsabers (Upgrade +2/+3) — attached unit gains "On Attack: you may give a Shield
#// to an enemy unit; if you do, the next event you play this phase costs 2 less." SEC_080 (wearing it)
#// attacks LAW_124, shields it, then Evade Arrest (cost 3) plays for 1 (3 resources → 2 left) — proving the
#// -2 discount, which only arms if the Shield was given.
## GIVEN
CommonSetup: yyk/rrk/{myResources:3;handCardIds:TS26_82}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_35
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1RESAVAILABLE:2

---

# AttachesOnlyToNonVehicleUnits
#// TS26_35 Ahsoka's Lightsabers — "Attach to a non-Vehicle unit." The offer is every non-Vehicle unit in
#// the GROUND or SPACE arena on EITHER side (there is no friendly-only clause), so the friendly SOR_095
#// and the enemy Wampa are legal while the friendly Vehicle ASH_261 Noti Mobile Pod is not. The friendly
#// space TIE token is a Vehicle too, so it is likewise absent.

## GIVEN
CommonSetup: yyk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_35
WithP1GroundArena: [SOR_095:1:0 ASH_261:1:0]
WithP1SpaceArena: JTL_T01:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0

---

# DiscountDoesNotCarryToTheNextPhase
#// TS26_35 Ahsoka's Lightsabers — "the next event you play THIS PHASE". The Shield is given on attack,
#// arming the -2, but the phase is passed out and the next round's resource step declined before Evade
#// Arrest (TS26_82, cost 3 on-aspect here) is played. It costs the full 3, draining the pool to 0.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
SkipPreGame: true
WithInitiativePlayer: 1
WithP1Hand: TS26_82
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_35
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESAVAILABLE:0

---

# WhenDefeatedInCombat_FiresAndStacksWithOnAttack
#// TS26_35 Ahsoka's Lightsabers — the grant is "On Attack/WHEN DEFEATED", two windows on one card. The
#// wearer (SEC_080 at 5/6 with the upgrade) attacks Army of the Dead (7/6): it shields the Wampa on
#// attack, then dies to the 7-power counter and shields the Wampa AGAIN on its own defeat. Both arms are
#// still unspent, so Rival's Fall (SHD_079, 6 printed + 2 for the uncovered Vigilance = 8) costs 4 and
#// leaves 4 of the 8 resources — and it defeats the Wampa through both Shields, since Shields stop damage,
#// not a defeat effect.

## GIVEN
CommonSetup: yyk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_35
WithP2GroundArena: [LOF_236:1:0 SOR_164:1:0]
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:theirGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P1RESAVAILABLE:4

---

# WhenDefeatedByAnEvent_ShieldsAndDiscounts
#// TS26_35 Ahsoka's Lightsabers — the When Defeated half does not need combat. P1 turns Rival's Fall on
#// their OWN wearer (cost 8 here): the unit dies, its granted When Defeated shields the enemy Wampa and
#// arms the -2, and Evade Arrest then costs 1 instead of 3. 10 - 8 - 1 = 1 resource left.

## GIVEN
CommonSetup: yyk/rrk/{myResources:10}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SHD_079 TS26_82]
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_35
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1RESAVAILABLE:1

---

# TwoCopiesArmedBeforeAnyEvent_StackToMinusFour
#// TS26_35 Ahsoka's Lightsabers — two wearers each attack the base and each shield the enemy, arming the
#// discount TWICE before a single event is played. Rival's Fall (8 here) then costs 4, leaving 2 of 6 —
#// and both arms are spent.
#// Discriminating: a flat "one -2, remove one charge" reading leaves 0 resources and the event unpaid-for.

## GIVEN
CommonSetup: yyk/rrk/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_079
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:TS26_35
WithP1GroundArenaUpgrade: 1:TS26_35
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:2
P2GROUNDARENACOUNT:0

---

# SeparateActivations_EachEventGetsItsOwnDiscount
#// TS26_35 Ahsoka's Lightsabers — arming and spending alternately also works: attack, shield, play Evade
#// Arrest for 1; attack with the second wearer, shield, play the second Evade Arrest for 1. 8 - 1 - 1 = 6.
#// Pairs with the stacking section: the same two triggers spent one at a time cost the same in total.

## GIVEN
CommonSetup: yyk/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [TS26_82 TS26_82]
WithP1GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1GroundArenaUpgrade: 0:TS26_35
WithP1GroundArenaUpgrade: 1:TS26_35
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESAVAILABLE:6

---

# NoEnemyUnitToShield_NoTriggerAndNoDiscount
#// TS26_35 Ahsoka's Lightsabers — with no enemy unit in play there is nobody to give the Shield to, so the
#// "if you do" never happens and nothing is armed. The wearer attacks P2's base for 5 (3 printed + 2 from
#// the upgrade) and Evade Arrest costs the full 3, emptying the pool.

## GIVEN
CommonSetup: yyk/rrk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: TS26_82
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:TS26_35
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1RESAVAILABLE:0
P2BASEDMG:5
