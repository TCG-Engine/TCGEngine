# ProtectsUpgradedUnitFromEnemyBounce
#// LOF_073 Mythosaur — "Friendly upgraded units can't be exhausted or returned to hand by enemy card
#// abilities." P1 controls Mythosaur + an UPGRADED unit (SOR_046 bearing SOR_214). P2 plays SHD_206 Spare
#// the Target (return an enemy non-leader unit to hand) targeting the upgraded unit → the bounce is
#// PREVENTED, so it stays in P1's arena.

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_214
WithP2Hand: SHD_206
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046

---

# DoesNotProtectNonUpgradedUnit
#// LOF_073 Mythosaur — the protection is UPGRADED-only. Same setup but SOR_046 has no upgrade, so SHD_206's
#// enemy bounce resolves normally and it leaves P1's arena (only Mythosaur remains). Proves the previous
#// segment's protection is conditional on the unit being upgraded, not a blanket immunity.

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP2Hand: SHD_206
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1

---

# GrantsMandalorianTraitToFriendlyLeader
#// LOF_073 Mythosaur — "Friendly leaders gain the Mandalorian trait." OBSERVABLE via SHD_073 Mandalorian
#// Armor ("When Played: if attached unit is a Mandalorian, give it a Shield"). P1's deployed leader Yoda
#// (TWI_004 — Force/Jedi/Republic, NOT printed Mandalorian) is made a Mandalorian by Mythosaur, so playing
#// SHD_073 onto Yoda grants him a Shield token. (Regression: the Mandalorian-to-leaders grant was
#// unimplemented — SHD_073 saw a non-Mandalorian leader and gave no shield.)

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TWI_004;myLeaderDeployed:true;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_073:1:0
WithP1Hand: SHD_073
WithP1Resources: 4

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_004
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1

---

# NoMythosaur_LeaderIsNotMandalorian_NoShield
#// Control: without Mythosaur in play, Yoda is not a Mandalorian, so SHD_073 Mandalorian Armor attaches but
#// grants NO shield. Proves the shield in the previous segment comes from Mythosaur's trait grant, not from
#// SHD_073 unconditionally.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:TWI_004;myLeaderDeployed:true;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SHD_073
WithP1Resources: 4

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_004
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0

---

# ExhaustPrevention_EnemyEvent_Outmaneuver
#// LOF_073 Mythosaur — "Friendly upgraded units can't be exhausted by enemy card abilities." P2 plays
#// SOR_221 Outmaneuver (exhaust each unit in an arena) choosing Ground. P1's UPGRADED SOR_046 (bearing
#// SOR_214) is NOT exhausted; the NON-upgraded SOR_095 and the un-upgraded Mythosaur ARE exhausted — proving
#// the protection is AOE-wide but upgraded-only. Intended: "prevents ... by enemy event abilities."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_214
WithP1GroundArena: SOR_095:1:0
WithP2Hand: SOR_221
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:Ground

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:2:EXHAUSTED

---

# NoPrevention_FriendlyExhaust_InPursuit
#// LOF_073 Mythosaur — the protection is against ENEMY abilities only. P1 plays its own TWI_221 In Pursuit
#// (exhaust a friendly unit; if you do, exhaust an enemy unit) and exhausts its own UPGRADED SOR_046. A
#// friendly source is not prevented, so SOR_046 is exhausted. Intended: "does not prevent ... by friendly card
#// abilities."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_214
WithP2GroundArena: SOR_095:1:0
WithP1Hand: TWI_221
WithP1Resources: 2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# NoPrevention_ExhaustAsStepOfAttack
#// LOF_073 Mythosaur — the protection blocks ENEMY-ability exhaust, not the normal exhaust from attacking.
#// An UPGRADED Mythosaur (bearing a Shield token SOR_T02) attacks P2's base; it exhausts as part of the
#// attack and the base takes its full 10 power. Intended: "does not prevent ... as part of the steps of an attack."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_073
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:10

---

# ReturnPrevention_EnemyUnitTriggered_CantinaBouncer
#// LOF_073 Mythosaur — protection covers an enemy UNIT'S triggered return. P2 plays SOR_202 Cantina Bouncer
#// ("When Played: you may return a non-leader unit to hand") targeting P1's UPGRADED SOR_046. The return is
#// prevented, so SOR_046 stays. Intended: "prevents return ... by enemy card triggered abilities."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_214
WithP2Hand: SOR_202
WithP2Resources: 5

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046

---

# NoPrevention_FriendlyReturn_Waylay
#// LOF_073 Mythosaur — return protection is against ENEMY abilities only. P1 plays its own SOR_222 Waylay to
#// return its UPGRADED SOR_046 to hand; a friendly source is not prevented, so SOR_046 leaves and only
#// Mythosaur remains. (Waylay is Cunning, off-aspect for this Vigilance/Villainy deck → costs 3+2=5.)
#// Intended: "does not prevent return ... by friendly card abilities."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_214
WithP1Hand: SOR_222
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_073

---

# ReturnPrevention_AOE_Evacuate
#// LOF_073 Mythosaur — "friendly upgraded units can't be returned to hand by enemy abilities" must hold on a
#// simultaneous mass-return (SHD_233 Evacuate, "return EACH non-leader unit"). P1 has Mythosaur + an upgraded
#// SOR_046 (with LOF_102 upgrade). P2 casts Evacuate: Mythosaur + P2's non-upgraded units return, but P1's
#// UPGRADED SOR_046 stays (protection evaluated against the pre-resolution board, Mythosaur still present).
## GIVEN
CommonSetup: bbw/yyk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:LOF_102
WithP2Hand: SHD_233
WithP2Resources: 6
## WHEN
- P2>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ExhaustPrevention_EnemyUnitActionAbility
#// LOF_073 Mythosaur — "Friendly upgraded units can't be exhausted … by enemy card abilities." The
#// existing prevention section drives an enemy EVENT; this drives an enemy UNIT'S ACTIVATED ABILITY, a
#// different dispatch path (SWUUnitAction rather than the event flow). P2's TWI_206 Independent Senator
#// ("Action [2 resources, Exhaust]: exhaust a unit with 4 or less power") targets P1's upgraded SOR_046
#// (3/7 + SOR_214 = 4/8, so power 4 is in range). The exhaust must be prevented — P1's unit stays READY
#// while the Senator itself still pays its own Exhaust cost.

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 1:SOR_214
WithP2GroundArena: TWI_206:1:0
WithP2Resources: 2

## WHEN
- P2>UseUnitAbility:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:READY
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# NoPrevention_ExhaustPaidAsOwnAbilityCost
#// LOF_073 Mythosaur — the protection is scoped to ENEMY card abilities, so it must NOT stop a friendly
#// upgraded unit from exhausting ITSELF to pay its own ability cost. P1's TWI_206 is upgraded (0/4 +
#// SOR_214 = 1/5) and therefore protected, but activating its own "Action [2 resources, Exhaust]" has to
#// spend that Exhaust — otherwise the unit would use the ability for free, repeatedly.
#// This is the cost-vs-effect line: OnExhaustCard allows the exhaust when the actor IS the controller.

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021;myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_073:1:0
WithP1GroundArena: TWI_206:1:0
WithP1GroundArenaUpgrade: 1:SOR_214
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseUnitAbility:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:TWI_206
P1GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED

---

# EnemyDeployedLeader_DoesNotGainMandalorian
#// LOF_073 Mythosaur — "FRIENDLY leaders gain the Mandalorian trait." The grant is scoped to the
#// Mythosaur controller's own leaders, so an ENEMY deployed leader must not pick it up. P1 controls
#// Mythosaur; P2's leader is deployed as a unit and must NOT be a Mandalorian.

## GIVEN
CommonSetup: bbw/rrk/{theirLeader:TWI_004;theirLeaderDeployed:true;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LOF_073:1:0

## WHEN

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:TWI_004
P2GROUNDARENAUNIT:0:NOTTRAIT:Mandalorian
P1GROUNDARENAUNIT:0:CARDID:LOF_073

---

# PilotLeaderHost_GainsMandalorian
#// LOF_073 Mythosaur — a unit carrying a LEADER PILOT upgrade is itself a leader unit, so it counts as
#// a "friendly leader" and gains the Mandalorian trait. P1's Academy Defense Walker (SOR_037, a ground
#// Vehicle with no printed Mandalorian trait) hosts the deployed leader-pilot Asajj Ventress; with
#// Mythosaur in play the Walker must read as a Mandalorian. Distinct from the existing grant section,
#// which uses a leader deployed as its own UNIT.

## GIVEN
CommonSetup: bbw/rrk/{myLeader:JTL_001;myLeaderDeployedPilot:true;myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_037:1:0
WithP1GroundArena: LOF_073:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_037
P1GROUNDARENAUNIT:0:HASTRAIT:Mandalorian
P1GROUNDARENAUNIT:1:CARDID:LOF_073
