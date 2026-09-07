# WhenPlayed_WithTarkin_GivesEnemyMinus3_NoSelfExhaust
#// HMW_206 The Tarkin Doctrine (Fortify, cost 1) — "When Played: If you control Grand Moff Tarkin, give an
#// enemy unit -3/-0 for this phase." P1's leader is HMW_004 Grand Moff Tarkin. The enemy SEC_080 (3/3)
#// drops to 0/3. It is placed READY and must STAY ready — proving the base-grant ("play a Fortification
#// upgrade → exhaust an enemy") does NOT self-trigger when The Tarkin Doctrine itself is played (its trait
#// is Law, not Fortification).

## GIVEN
CommonSetup: yyk/rrk/{myLeader:HMW_004;myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_206
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:0:READY

---

# WhenPlayed_WithoutTarkin_NoDebuff
#// The gate: without Grand Moff Tarkin, the -3/-0 does not happen (the leader here is Thrawn, yk).

## GIVEN
CommonSetup: yyk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_206
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:POWER:3

---

# GrantedBaseAbility_PlayingAFortificationUpgradeExhaustsAnEnemyUnit
#// "Attached base gains: 'When you play a Fortification upgrade: Exhaust an enemy unit.'" With The Tarkin
#// Doctrine already on the base, playing HMW_095 Carbonite Chamber (a Fortification-trait upgrade) exhausts
#// the lone enemy unit.

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_206
WithP1Hand: HMW_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# GrantedBaseAbility_NotActiveWithoutTheTarkinDoctrine
#// Control: the same Fortification-upgrade play with NO Tarkin Doctrine on the base does not exhaust anyone
#// (the grant is only present while HMW_206 is attached).

## GIVEN
CommonSetup: bbk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_095
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:READY

---

# GrantedBaseAbility_NonFortificationUpgrade_DoesNotTrigger
#// THE TRAIT GATE. "When you play a FORTIFICATION upgrade" — an ordinary unit upgrade is still an
#// upgrade and still a play, so a grant that listened for "an upgrade" fires here and is caught by
#// nothing else in this file. SOR_120 Academy Training goes onto a friendly unit and the enemy stays
#// ready.
## GIVEN
CommonSetup: bbk/rrk/{myResources:6}
P1OnlyActions: true
WithP1BaseUpgrade: HMW_206
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SOR_120
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:READY

---

# GrantedBaseAbility_OpponentsOwnFortification_DoesNotTrigger
#// "When YOU play a Fortification upgrade" — the grant belongs to the attached base's controller, so an
#// opponent fortifying THEIR OWN base must not fire it. Without this, a grant listening for any
#// Fortification play anywhere on the table passes every other section here, and would hand P1 a free
#// exhaust every time P2 developed their own base.
#// P2 plays HMW_112 Military Academy onto their base; P1's own unit is the only thing that could be
#// exhausted, and it stays ready.
## GIVEN
CommonSetup: bbk/rrk/{theirResources:8}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1BaseUpgrade: HMW_206
WithP1GroundArena: SOR_095:1:0
WithP2Hand: HMW_112
## WHEN
- P2>PlayHand:0
## EXPECT
P2BASE:UPGRADECOUNT:1
P1BASE:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:READY

---

# GrantedBaseAbility_ExhaustOfferIsENEMYOnly
#// THE OFFER ITSELF. The granted ability reads "Exhaust an ENEMY unit", and every other section here
#// drives it on a board with a single enemy — where the choose auto-resolves and the pool is never
#// examined. A pool that had drifted to "any unit" would pass all of them.
#// Board: two enemy units and a friendly one. Exactly the two enemies come back.
## GIVEN
CommonSetup: bbk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1BaseUpgrade: HMW_206
WithP1GroundArena: SOR_095:1:0
WithP1Hand: HMW_095
WithP2GroundArena: [SEC_080:1:0 SOR_046:1:0]
## WHEN
- P1>PlayHand:0
## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# RequestBoundary_ThePhaseDebuffSurvives
#// The request-boundary cell, in the form a card with no interactive decision needs it: the When Played
#// writes PHASE-SCOPED state (-3/-0 until end of phase) and nothing reads it back until a later request.
#// A debuff held anywhere but the serialized turn-effect list is gone by then, and the unit silently
#// reads at printed power again.
#// Same GIVEN and EXPECT as WhenPlayed_WithTarkin_GivesEnemyMinus3_NoSelfExhaust, with a boundary
#// inserted after the play — so every stat below is read in a fresh request.
## GIVEN
CommonSetup: yyk/rrk/{myLeader:HMW_004;myResources:1}
P1OnlyActions: true
WithP1Hand: HMW_206
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
## EXPECT
P1BASE:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:3
P2GROUNDARENAUNIT:0:READY
